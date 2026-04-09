<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordSetupController extends Controller
{
    // =========================================================================
    // PAGES — PASSWORD SETUP (akun baru)
    // =========================================================================

    /**
     * Halaman "Silakan cek email Anda".
     * type=setup  → akun baru (default)
     * type=reset  → forgot password
     */
    public function showCheckEmail(Request $request)
    {
        $email = $request->query('email', '');
        $type  = $request->query('type', 'setup'); // 'setup' | 'reset'
        return view('auth.check-email', compact('email', 'type'));
    }

    /**
     * Halaman form atur/reset password — ditampilkan saat user klik link di email.
     */
    public function showChangePassword(Request $request)
    {
        $token = $request->query('token', '');

        if (empty($token)) {
            return redirect()->route('login')->with('error', 'Invalid link.');
        }

        $authUser = DB::table('auth_users')
            ->where('cp_token', $token)
            ->where('cp_token_expires_at', '>', now())
            ->first();

        if (!$authUser) {
            return redirect()->route('login')
                ->with('error', 'This link has expired. Please request a new one.');
        }

        return view('auth.change-password', compact('token'));
    }

    /**
     * Proses simpan password baru (digunakan untuk setup akun baru & reset password).
     */
    public function submitChangePassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $authUser = DB::table('auth_users')
            ->where('cp_token', $request->token)
            ->where('cp_token_expires_at', '>', now())
            ->first();

        if (!$authUser) {
            return back()->withErrors([
                'token' => 'This link has expired. Please request a new one.',
            ]);
        }

        DB::table('auth_users')->where('id', $authUser->id)->update([
            'password'            => Hash::make($request->password),
            'is_already_cp'       => true,
            'cp_token'            => null,
            'cp_token_expires_at' => null,
            'updated_at'          => now(),
        ]);

        Log::info('PasswordSetupController: password berhasil diubah', [
            'auth_user_id' => $authUser->id,
        ]);

        // Jika customer → auto-login dan arahkan langsung ke dashboard
        if ($authUser->customer_id) {
            $customer = Customer::with('basicData')
                ->where('customer_id', $authUser->customer_id)
                ->first();

            if ($customer) {
                $companyName = $customer->basicData->name_1 ?? $authUser->email;

                // Fetch contact person profile (same logic as AuthController)
                $contact = null;
                if (!empty($authUser->contact_id)) {
                    $contact = DB::table('customer_contact')
                        ->where('contact_id', $authUser->contact_id)
                        ->select('contact_id', 'full_name', 'position', 'department', 'cell_phone', 'email_work')
                        ->first();
                }
                if (!$contact) {
                    $contact = DB::table('customer_contact')
                        ->where('customer_id', $authUser->customer_id)
                        ->orderBy('contact_id')
                        ->select('contact_id', 'full_name', 'position', 'department', 'cell_phone', 'email_work')
                        ->first();
                }

                $token    = base64_encode($customer->customer_code . '|' . time() . '|customer');
                $userData = [
                    'id'            => $customer->customer_id,
                    'type'          => 'customer',
                    'customer_code' => $customer->customer_code,
                    'contact_id'    => $contact->contact_id ?? null,
                    'name'          => $contact->full_name ?? $authUser->username,
                    'company_name'  => $companyName,
                    'email'         => $authUser->email,
                    'position'      => $contact->position ?? null,
                    'department'    => $contact->department ?? null,
                    'phone'         => $contact->cell_phone ?? null,
                    'category'      => $customer->basicData->customer_category ?? null,
                    'group'         => $customer->basicData->customer_group ?? null,
                    'role'          => ['id' => 3, 'name' => 'Customer'],
                ];

                $request->session()->put('auth_token', $token);
                $request->session()->put('user', $userData);
                $request->session()->regenerate();

                Log::info('PasswordSetupController: auto-login customer setelah setup password', [
                    'customer_id' => $customer->customer_id,
                ]);

                return redirect()->route('dashboard');
            }
        }

        // Employee or customer not found → redirect to login as usual
        return redirect()->route('login')
            ->with('success', 'Password set successfully. Please log in with your new password.');
    }

    // =========================================================================
    // PAGES — FORGOT PASSWORD
    // =========================================================================

    /**
     * Halaman form forgot password — user masukkan email terdaftar.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Proses forgot password:
     * - Cari auth_user berdasarkan email
     * - Generate token & kirim email reset
     * - Redirect ke halaman "cek email"
     */
    public function submitForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $authUser = DB::table('auth_users')
            ->where('email', $request->email)
            ->where('is_active', true)
            ->first();

        // Selalu tampilkan pesan sukses agar tidak mengekspos info akun (security)
        if (!$authUser) {
            $maskedEmail = $this->maskEmail($request->email);
            return redirect()->route('password-setup.check-email', [
                'email' => $maskedEmail,
                'type'  => 'reset',
            ]);
        }

        self::generateAndSendToken($authUser, 'reset');

        [$local, $domain] = explode('@', $authUser->email, 2);
        $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;

        Log::info('PasswordSetupController: forgot password link dikirim', [
            'auth_user_id' => $authUser->id,
        ]);

        return redirect()->route('password-setup.check-email', [
            'email' => $maskedEmail,
            'type'  => 'reset',
        ]);
    }

    // =========================================================================
    // HELPER: Generate token & kirim email
    // =========================================================================

    /**
     * Generate token, simpan ke auth_users, kirim email.
     * $type: 'setup' (akun baru) | 'reset' (forgot password)
     * Dipanggil dari AuthController (setup) dan submitForgotPassword (reset).
     */
    public static function generateAndSendToken(object $authUser, string $type = 'setup'): void
    {
        $token   = Str::random(64);
        $expires = now()->addHours(24);

        DB::table('auth_users')->where('id', $authUser->id)->update([
            'cp_token'            => $token,
            'cp_token_expires_at' => $expires,
            'updated_at'          => now(),
        ]);

        if (empty($authUser->email)) {
            Log::warning('PasswordSetupController: tidak bisa kirim email — auth_user tidak punya email', [
                'auth_user_id' => $authUser->id,
            ]);
            return;
        }

        try {
            $link       = route('password-setup.change', ['token' => $token]);
            $appName    = config('app.name', 'ECoSystem');
            $senderName = env('MS_SENDER_NAME', $appName);
            $appUrl     = rtrim(config('app.url', ''), '/');

            if ($type === 'reset') {
                $subject  = "Password Reset Request - {$appName}";
                $bodyHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <tr><td style="background:#7f1d1d;padding:24px 32px;">
          <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">{$appName}</p>
        </td></tr>
        <tr><td style="padding:32px;">
          <p style="margin:0 0 16px;font-size:15px;color:#374151;">Hello,</p>
          <p style="margin:0 0 16px;font-size:15px;color:#374151;">
            We received a request to reset the password for your account registered in the <strong>{$appName}</strong> system.
          </p>
          <p style="margin:0 0 24px;font-size:15px;color:#374151;">
            Click the link below to create a new password. This link is valid for <strong>24 hours</strong>.
          </p>
          <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
            <tr><td style="background:#991b1b;border-radius:6px;padding:12px 28px;">
              <a href="{$link}" style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;display:block;">Reset My Password</a>
            </td></tr>
          </table>
          <p style="margin:0 0 12px;font-size:13px;color:#6b7280;">
            Or copy and paste the following link into your browser:
          </p>
          <p style="margin:0 0 24px;font-size:12px;color:#9ca3af;word-break:break-all;">{$link}</p>
          <p style="margin:0 0 16px;font-size:13px;color:#6b7280;">
            If you did not request a password reset, please ignore this email. Your password will not change.
          </p>
          <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
          <p style="margin:0;font-size:13px;color:#9ca3af;">
            This email was sent automatically by the {$appName} system. Please do not reply to this email.
          </p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;">
          <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
            &copy; {$appName} &mdash; PT Eclectic Consulting Yogyakarta<br>
            <a href="{$appUrl}" style="color:#9ca3af;">{$appUrl}</a>
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
                $bodyText = "Hello,\r\n\r\nWe received a request to reset your {$appName} account password.\r\n\r\nUse the following link to create a new password (valid for 24 hours):\r\n{$link}\r\n\r\nIf you did not request this, please ignore this email.\r\n\r\nBest regards,\r\nThe {$appName} Team";
            } else {
                $subject  = "Welcome to {$appName} - Complete Your Account Setup";
                $bodyHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <tr><td style="background:#7f1d1d;padding:24px 32px;">
          <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">{$appName}</p>
        </td></tr>
        <tr><td style="padding:32px;">
          <p style="margin:0 0 16px;font-size:15px;color:#374151;">Hello,</p>
          <p style="margin:0 0 16px;font-size:15px;color:#374151;">
            Your account in the <strong>{$appName}</strong> system has been successfully created by our team.
          </p>
          <p style="margin:0 0 24px;font-size:15px;color:#374151;">
            To log in to the system, you need to set your password first.
            Click the link below to continue. This link is valid for <strong>24 hours</strong>.
          </p>
          <table cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
            <tr><td style="background:#991b1b;border-radius:6px;padding:12px 28px;">
              <a href="{$link}" style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;display:block;">Set My Password</a>
            </td></tr>
          </table>
          <p style="margin:0 0 12px;font-size:13px;color:#6b7280;">
            Or copy and paste the following link into your browser:
          </p>
          <p style="margin:0 0 24px;font-size:12px;color:#9ca3af;word-break:break-all;">{$link}</p>
          <p style="margin:0 0 16px;font-size:13px;color:#6b7280;">
            If you did not register or received this email by mistake, please contact our administrator.
          </p>
          <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
          <p style="margin:0;font-size:13px;color:#9ca3af;">
            This email was sent automatically by the {$appName} system. Please do not reply to this email.
          </p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;">
          <p style="margin:0;font-size:12px;color:#9ca3af;text-align:center;">
            &copy; {$appName} &mdash; PT Eclectic Consulting Yogyakarta<br>
            <a href="{$appUrl}" style="color:#9ca3af;">{$appUrl}</a>
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
                $bodyText = "Hello,\r\n\r\nYour account in {$appName} has been created by our team.\r\n\r\nUse the following link to set your password (valid for 24 hours):\r\n{$link}\r\n\r\nIf you have any questions, please contact the administrator.\r\n\r\nBest regards,\r\nThe {$appName} Team";
            }

            $sender     = env('MS_SENDER_EMAIL');
            $graphToken = self::getGraphToken();

            Http::withToken($graphToken)->post(
                rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/') . "/users/{$sender}/sendMail",
                [
                    'message' => [
                        'subject' => $subject,
                        'body'    => ['contentType' => 'HTML', 'content' => $bodyHtml],
                        'from'    => [
                            'emailAddress' => [
                                'name'    => $senderName,
                                'address' => $sender,
                            ],
                        ],
                        'replyTo' => [
                            ['emailAddress' => ['name' => $senderName, 'address' => $sender]],
                        ],
                        'toRecipients' => [
                            ['emailAddress' => ['address' => $authUser->email]],
                        ],
                    ],
                    'saveToSentItems' => true,
                ]
            );

            Log::info('PasswordSetupController: email terkirim', [
                'auth_user_id' => $authUser->id,
                'email'        => $authUser->email,
                'type'         => $type,
            ]);
        } catch (\Exception $e) {
            Log::error('PasswordSetupController: gagal kirim email', [
                'auth_user_id' => $authUser->id,
                'type'         => $type,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mask email untuk ditampilkan ke user: "ab***@domain.com"
     */
    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) return $email;
        [$local, $domain] = explode('@', $email, 2);
        return substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;
    }

    /**
     * Ambil OAuth2 access token dari Microsoft.
     */
    private static function getGraphToken(): string
    {
        $tenantId = env('MS_TENANT_ID');
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => env('MS_CLIENT_ID'),
                'client_secret' => env('MS_CLIENT_SECRET'),
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to obtain access token: ' . $response->body());
        }

        return $response->json('access_token');
    }
}
