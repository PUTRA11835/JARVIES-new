<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ProvisionsContactLogin;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PasswordSetupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API Auth Controller (Mobile - Customer Only)
 *
 * Login, logout, dan profil customer.
 * Menggunakan token stateless (Bearer) — tidak ada session.
 */
class AuthController extends Controller
{
    use ProvisionsContactLogin;

    /**
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $identifier = trim($request->email);

        try {
            // Cari di auth_users: email, username, atau phone
            $authUser = DB::table('auth_users')
                ->where(function ($q) use ($identifier) {
                    $q->where('email', $identifier)
                        ->orWhere('username', $identifier)
                        ->orWhere('phone', $identifier);
                })
                ->where('is_active', true)
                ->first();

            // SELF-SERVE: no login account yet. Provision one from customer master data
            // if this email belongs to a registered customer contact (same outcome as an
            // admin clicking "Grant Access" in EcoSystem). Otherwise reject generically.
            if (!$authUser) {
                $authUser = $this->provisionContactLogin($identifier);
            }

            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            // Hanya customer yang boleh akses mobile
            if (is_null($authUser->customer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account does not have access to the mobile app.',
                ], 403);
            }

            // User belum set password — kirim email setup DULU (sebelum cek password,
            // karena akun yang belum di-setup hanya punya password acak)
            if (!$authUser->is_already_cp) {
                if (empty($authUser->email)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account does not have a registered email. Please contact the administrator.',
                    ], 403);
                }

                PasswordSetupController::generateAndSendToken($authUser);

                [$local, $domain] = explode('@', $authUser->email, 2);
                $maskedEmail = substr($local, 0, 2)
                    . str_repeat('*', max(strlen($local) - 2, 3))
                    . '@' . $domain;

                return response()->json([
                    'success'                 => false,
                    'require_password_change' => true,
                    'message'                 => 'Please check your email to set your password first.',
                    'email'                   => $maskedEmail,
                ], 403);
            }

            // Akun sudah set password — verifikasi password
            if (!Hash::check($request->password, $authUser->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            // Ambil data customer
            $customer = DB::table('customer as c')
                ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
                ->where('c.customer_id', $authUser->customer_id)
                ->where('c.is_active', true)
                ->select(
                    'c.customer_id',
                    'c.customer_code',
                    'c.email',
                    'c.is_active',
                    'cb.title',
                    'cb.name_1',
                    'cb.name_2',
                    'cb.customer_category',
                    'cb.customer_group'
                )
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive.',
                ], 403);
            }

            DB::table('auth_users')->where('id', $authUser->id)->update(['last_login_at' => now()]);

            $accessToken  = base64_encode($customer->customer_code . '|' . time() . '|customer');
            $refreshToken = $this->generateRefreshToken($customer->customer_id);
            $companyName  = trim($customer->title . ' ' . $customer->name_1 . ' ' . ($customer->name_2 ?? ''));

            $userData = [
                'id'            => $customer->customer_id,
                'type'          => 'customer',
                'customer_code' => $customer->customer_code,
                'company_name'  => $companyName,
                'email'         => $authUser->email,
                'category'      => $customer->customer_category,
                'group'         => $customer->customer_group,
                // 'role'          => ['id' => 3, 'name' => 'Customer'],
            ];

            Log::channel('daily')->info('Mobile API: Customer login', [
                'customer_id' => $customer->customer_id,
                'ip'          => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => [
                    'access_token'  => $accessToken,
                    'refresh_token' => $refreshToken,
                    // 'expires_in'    => 7 * 24 * 3600, // 604800 detik (7 hari)
                    'token_type'    => 'Bearer',
                    'user'          => $userData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile API: login error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'A system error occurred.',
            ], 500);
        }
    }

    /**
     * POST /api/auth/logout
     * Hapus refresh token dari DB agar tidak bisa digunakan lagi.
     *
     * Body (opsional):
     *   refresh_token  string
     */
    public function logout(Request $request)
    {
        $user = $request->attributes->get('api_user');

        if ($request->filled('refresh_token')) {
            DB::table('api_refresh_tokens')
                ->where('token', $request->refresh_token)
                ->where('customer_id', $user['id'])
                ->delete();
        } else {
            // Hapus semua refresh token customer ini (logout semua device)
            DB::table('api_refresh_tokens')
                ->where('customer_id', $user['id'])
                ->delete();
        }

        Log::channel('daily')->info('Mobile API: Customer logout', [
            'customer_id' => $user['id'] ?? null,
            'ip'          => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * POST /api/auth/refresh
     * Tukar refresh token dengan access token baru (rotating refresh token).
     *
     * Body:
     *   refresh_token  (required)
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token is required.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $tokenRecord = DB::table('api_refresh_tokens')
                ->where('token', $request->refresh_token)
                ->where('expires_at', '>', now())
                ->first();

            if (!$tokenRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token is invalid or has expired. Please log in again.',
                    'code'    => 'REFRESH_TOKEN_INVALID',
                ], 401);
            }

            $customer = DB::table('customer as c')
                ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
                ->where('c.customer_id', $tokenRecord->customer_id)
                ->where('c.is_active', true)
                ->select('c.customer_id', 'c.customer_code')
                ->first();

            if (!$customer) {
                DB::table('api_refresh_tokens')->where('id', $tokenRecord->id)->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive.',
                ], 401);
            }

            // Rotate: hapus token lama, buat token baru
            DB::table('api_refresh_tokens')->where('id', $tokenRecord->id)->delete();

            $newAccessToken  = base64_encode($customer->customer_code . '|' . time() . '|customer');
            $newRefreshToken = $this->generateRefreshToken($customer->customer_id);

            return response()->json([
                'success' => true,
                'data'    => [
                    'access_token'  => $newAccessToken,
                    'refresh_token' => $newRefreshToken,
                    'expires_in'    => 7 * 24 * 3600,
                    'token_type'    => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile API: refresh error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'A system error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/auth/me
     * Profil customer dari token.
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->attributes->get('api_user'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────

    /**
     * Generate refresh token baru dan simpan ke DB.
     * Token lama yang sudah expire untuk customer ini akan dibersihkan otomatis.
     */
    private function generateRefreshToken(int $customerId): string
    {
        // Bersihkan token expired milik customer ini
        DB::table('api_refresh_tokens')
            ->where('customer_id', $customerId)
            ->where('expires_at', '<', now())
            ->delete();

        $token = bin2hex(random_bytes(32)); // 64 karakter hex

        DB::table('api_refresh_tokens')->insert([
            'customer_id' => $customerId,
            'token'       => $token,
            'expires_at'  => now()->addDays(90),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $token;
    }
}
