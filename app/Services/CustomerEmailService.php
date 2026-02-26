<?php

namespace App\Services;

use App\Models\CustomerEmailToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CustomerEmailService
 *
 * Handles sending email FROM the customer's linked account
 * to the company helpdesk mailbox.
 *
 * Providers supported:
 * - google : Gmail API (RFC 2822, base64url encoded)
 * - azure  : Microsoft Graph API delegated /me/sendMail
 */
class CustomerEmailService
{
    /**
     * Send an email using the customer's linked OAuth account.
     *
     * @param  CustomerEmailToken  $token   The stored OAuth token record
     * @param  string              $toEmail Recipient email address
     * @param  string              $subject Email subject
     * @param  string              $body    Plain-text body
     * @return bool                True on success, false on failure
     */
    public function sendEmail(CustomerEmailToken $token, string $toEmail, string $subject, string $body): bool
    {
        try {
            $accessToken = $this->getValidAccessToken($token);

            if ($token->provider === 'google') {
                return $this->sendViaGmail($accessToken, $token->provider_email, $toEmail, $subject, $body);
            } elseif ($token->provider === 'azure') {
                return $this->sendViaMicrosoftGraph($accessToken, $toEmail, $subject, $body);
            }

            Log::warning('CustomerEmailService: unknown provider', ['provider' => $token->provider]);
            return false;

        } catch (\Throwable $e) {
            Log::error('CustomerEmailService@sendEmail failed', [
                'provider'   => $token->provider,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    // =========================================================================
    // GMAIL (Google OAuth2 delegated)
    // =========================================================================

    /**
     * Send email via Gmail API using RFC 2822 raw message.
     */
    private function sendViaGmail(string $accessToken, string $fromEmail, string $toEmail, string $subject, string $body): bool
    {
        $raw = $this->buildRfc2822Message($fromEmail, $toEmail, $subject, $body);

        $response = Http::withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ]);

        if ($response->successful()) {
            Log::info('CustomerEmailService: Gmail send OK', ['to' => $toEmail]);
            return true;
        }

        Log::error('CustomerEmailService: Gmail send failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return false;
    }

    /**
     * Build RFC 2822 email and base64url-encode it for Gmail API.
     */
    private function buildRfc2822Message(string $from, string $to, string $subject, string $body): string
    {
        $message = implode("\r\n", [
            "From: {$from}",
            "To: {$to}",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 7bit",
            "",
            $body,
        ]);

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    // =========================================================================
    // MICROSOFT GRAPH (delegated — /me/sendMail)
    // =========================================================================

    /**
     * Send email via Microsoft Graph delegated /me/sendMail.
     */
    private function sendViaMicrosoftGraph(string $accessToken, string $toEmail, string $subject, string $body): bool
    {
        $baseUrl = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');

        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/me/sendMail", [
                'message' => [
                    'subject' => $subject,
                    'body'    => ['contentType' => 'Text', 'content' => $body],
                    'toRecipients' => [
                        ['emailAddress' => ['address' => $toEmail]],
                    ],
                ],
                'saveToSentItems' => true,
            ]);

        if ($response->successful()) {
            Log::info('CustomerEmailService: Microsoft Graph send OK', ['to' => $toEmail]);
            return true;
        }

        Log::error('CustomerEmailService: Microsoft Graph send failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return false;
    }

    // =========================================================================
    // TOKEN REFRESH
    // =========================================================================

    /**
     * Return a valid access token, refreshing if expired.
     */
    private function getValidAccessToken(CustomerEmailToken $token): string
    {
        if (!$token->isExpired()) {
            return $token->access_token;
        }

        if (!$token->refresh_token) {
            throw new \RuntimeException('Token expired and no refresh token available. User must re-authenticate.');
        }

        return $this->refreshToken($token);
    }

    /**
     * Refresh the OAuth access token using the stored refresh token.
     */
    private function refreshToken(CustomerEmailToken $token): string
    {
        if ($token->provider === 'google') {
            return $this->refreshGoogleToken($token);
        } elseif ($token->provider === 'azure') {
            return $this->refreshAzureToken($token);
        }

        throw new \RuntimeException('Unknown provider: ' . $token->provider);
    }

    private function refreshGoogleToken(CustomerEmailToken $token): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $token->refresh_token,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Google token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $token->update([
            'access_token'    => $data['access_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        Log::info('CustomerEmailService: Google token refreshed');
        return $data['access_token'];
    }

    private function refreshAzureToken(CustomerEmailToken $token): string
    {
        $tenantId = config('services.azure.tenant');
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id'     => config('services.azure.client_id'),
                'client_secret' => config('services.azure.client_secret'),
                'refresh_token' => $token->refresh_token,
                'grant_type'    => 'refresh_token',
                'scope'         => 'https://graph.microsoft.com/Mail.Send offline_access',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Azure token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $token->update([
            'access_token'    => $data['access_token'],
            'refresh_token'   => $data['refresh_token'] ?? $token->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        Log::info('CustomerEmailService: Azure token refreshed');
        return $data['access_token'];
    }
}
