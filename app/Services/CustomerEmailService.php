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
     * @param  CustomerEmailToken  $token     The stored OAuth token record
     * @param  string              $toEmail   Recipient email address
     * @param  string              $subject   Email subject
     * @param  string              $body      Plain-text body
     * @param  string|null         $customerThreadId  Gmail threadId to reply in same thread (optional)
     * @param  string|null         $inReplyTo         RFC 2822 Message-ID to set In-Reply-To header
     * @return string|null         RFC 2822 Message-ID of the sent email (for deduplication), null on failure
     */
    /**
     * @param  array  $fileAttachments  File dari tombol attachment: [['name','content','mime'], ...]
     * @param  array  $inlineImages     Gambar paste dari Quill: [['name','content','mime','cid'], ...]
     * @param  string $htmlBody         Quill HTML body (dengan cid: refs, sudah di-replace dari data URI)
     */
    public function sendEmail(
        CustomerEmailToken $token,
        string $toEmail,
        string $subject,
        string $body,
        ?string $customerThreadId = null,
        ?string $inReplyTo = null,
        array $fileAttachments = [],
        array $inlineImages = [],
        string $htmlBody = '',
        array $ccEmails = []
    ): ?string {
        try {
            $accessToken = $this->getValidAccessToken($token);

            if ($token->provider === 'google') {
                return $this->sendViaGmail(
                    $accessToken, $token->provider_email, $toEmail, $subject,
                    $body, $customerThreadId, $inReplyTo,
                    $fileAttachments, $inlineImages, $htmlBody, $ccEmails
                );
            } elseif ($token->provider === 'azure') {
                $ok = $this->sendViaMicrosoftGraph(
                    $accessToken, $toEmail, $subject, $body, $customerThreadId,
                    $fileAttachments, $inlineImages, $htmlBody, $ccEmails
                );
                return $ok ? 'azure_sent' : null;
            }

            Log::warning('CustomerEmailService: unknown provider', ['provider' => $token->provider]);
            return null;

        } catch (\Throwable $e) {
            Log::error('CustomerEmailService@sendEmail failed', [
                'provider' => $token->provider,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // GMAIL (Google OAuth2 delegated)
    // =========================================================================

    private function sendViaGmail(
        string $accessToken,
        string $fromEmail,
        string $toEmail,
        string $subject,
        string $body,
        ?string $threadId = null,
        ?string $inReplyTo = null,
        array $fileAttachments = [],
        array $inlineImages = [],
        string $htmlBody = '',
        array $ccEmails = []
    ): ?string {
        $hasFiles  = !empty($fileAttachments);
        $hasInline = !empty($inlineImages);

        if (!$hasFiles && !$hasInline) {
            // Plain text, no attachments
            $raw = $this->buildRfc2822Message($fromEmail, $toEmail, $subject, $body, $inReplyTo, $ccEmails);
        } else {
            $raw = $this->buildMultipartMessage(
                $fromEmail, $toEmail, $subject, $body, $htmlBody,
                $inReplyTo, $fileAttachments, $inlineImages, $ccEmails
            );
        }

        $payload = ['raw' => $raw];
        if ($threadId) {
            $payload['threadId'] = $threadId;
        }

        $response = Http::withToken($accessToken)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', $payload);

        if (!$response->successful()) {
            Log::error('CustomerEmailService: Gmail send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        $gmailMsgId = $response->json('id');
        Log::info('CustomerEmailService: Gmail send OK', [
            'to'            => $toEmail,
            'threadId'      => $response->json('threadId'),
            'id'            => $gmailMsgId,
            'files'         => count($fileAttachments),
            'inline_images' => count($inlineImages),
        ]);

        return $gmailMsgId;
    }

    /**
     * Build RFC 2822 plain-text email (no attachments).
     */
    private function buildRfc2822Message(string $from, string $to, string $subject, string $body, ?string $inReplyTo = null, array $ccEmails = []): string
    {
        $headers = [
            "From: {$from}",
            "To: {$to}",
        ];

        if (!empty($ccEmails)) {
            $headers[] = "Cc: " . implode(', ', $ccEmails);
        }

        $headers = array_merge($headers, [
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 7bit",
        ]);

        if ($inReplyTo) {
            $headers[] = "In-Reply-To: {$inReplyTo}";
            $headers[] = "References: {$inReplyTo}";
        }

        $headers[] = "";
        $headers[] = $body;

        return rtrim(strtr(base64_encode(implode("\r\n", $headers)), '+/', '-_'), '=');
    }

    /**
     * Build multipart/mixed RFC 2822 email.
     *
     * Struktur:
     *   multipart/mixed
     *     ├─ multipart/related  (jika ada inline images)
     *     │    ├─ text/html (htmlBody dengan cid: refs)
     *     │    └─ image/* (Content-Id, Content-Disposition: inline) × N
     *     │  ATAU text/plain (jika tidak ada inline images)
     *     └─ application/... (regular file attachments) × M
     *
     * @param  array $fileAttachments  [['name','content','mime'], ...]
     * @param  array $inlineImages     [['name','content','mime','cid'], ...]
     */
    private function buildMultipartMessage(
        string $from, string $to, string $subject,
        string $textBody, string $htmlBody,
        ?string $inReplyTo,
        array $fileAttachments,
        array $inlineImages,
        array $ccEmails = []
    ): string {
        $outer = 'jv_' . bin2hex(random_bytes(12));

        $headers = [
            "From: {$from}",
            "To: {$to}",
        ];

        if (!empty($ccEmails)) {
            $headers[] = "Cc: " . implode(', ', $ccEmails);
        }

        $headers = array_merge($headers, [
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/mixed; boundary=\"{$outer}\"",
        ]);

        if ($inReplyTo) {
            $headers[] = "In-Reply-To: {$inReplyTo}";
            $headers[] = "References: {$inReplyTo}";
        }

        $parts = '';

        if (!empty($inlineImages)) {
            // Body part: multipart/related (HTML + inline images)
            $inner   = 'jv_' . bin2hex(random_bytes(12));
            $safeHtml = $htmlBody ?: ('<p>' . htmlspecialchars($textBody) . '</p>');

            $parts .= "--{$outer}\r\n";
            $parts .= "Content-Type: multipart/related; boundary=\"{$inner}\"\r\n\r\n";

            // HTML body
            $parts .= "--{$inner}\r\n";
            $parts .= "Content-Type: text/html; charset=UTF-8\r\n";
            $parts .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $parts .= quoted_printable_encode($safeHtml) . "\r\n";

            // Inline image parts
            foreach ($inlineImages as $img) {
                $encoded  = chunk_split(base64_encode($img['content']), 76, "\r\n");
                $safeName = addslashes($img['name']);
                $parts .= "--{$inner}\r\n";
                $parts .= "Content-Type: {$img['mime']}; name=\"{$safeName}\"\r\n";
                $parts .= "Content-Transfer-Encoding: base64\r\n";
                $parts .= "Content-Disposition: inline; filename=\"{$safeName}\"\r\n";
                $parts .= "Content-Id: <{$img['cid']}>\r\n\r\n";
                $parts .= $encoded;
            }

            $parts .= "--{$inner}--\r\n";
        } else {
            // Body part: plain text
            $parts .= "--{$outer}\r\n";
            $parts .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $parts .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $parts .= ($textBody !== '' ? $textBody : '(Lampiran)') . "\r\n";
        }

        // Regular file attachments
        foreach ($fileAttachments as $att) {
            $encoded  = chunk_split(base64_encode($att['content']), 76, "\r\n");
            $safeName = addslashes($att['name']);
            $parts .= "--{$outer}\r\n";
            $parts .= "Content-Type: {$att['mime']}; name=\"{$safeName}\"\r\n";
            $parts .= "Content-Transfer-Encoding: base64\r\n";
            $parts .= "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n\r\n";
            $parts .= $encoded;
        }

        $parts .= "--{$outer}--";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $parts;

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    // =========================================================================
    // MICROSOFT GRAPH (delegated — /me/sendMail)
    // =========================================================================

    private function sendViaMicrosoftGraph(
        string $accessToken,
        string $toEmail,
        string $subject,
        string $body,
        ?string $conversationId = null,
        array $fileAttachments = [],
        array $inlineImages = [],
        string $htmlBody = '',
        array $ccEmails = []
    ): bool {
        $baseUrl   = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');
        $hasInline = !empty($inlineImages);

        $message = [
            'subject' => $subject,
            'body'    => $hasInline
                ? ['contentType' => 'HTML', 'content' => $htmlBody ?: ('<p>' . htmlspecialchars($body) . '</p>')]
                : ['contentType' => 'Text', 'content' => $body ?: '(Lampiran)'],
            'toRecipients' => [
                ['emailAddress' => ['address' => $toEmail]],
            ],
        ];

        if (!empty($ccEmails)) {
            $message['ccRecipients'] = array_map(
                fn($email) => ['emailAddress' => ['address' => trim($email)]],
                $ccEmails
            );
        }

        if ($conversationId && !str_starts_with($conversationId, 'azure_')) {
            $message['conversationId'] = $conversationId;
        }

        $graphAttachments = [];

        // Inline images: isInline=true, contentId untuk cid: reference di HTML body
        foreach ($inlineImages as $img) {
            $graphAttachments[] = [
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'name'         => $img['name'],
                'contentType'  => $img['mime'],
                'contentBytes' => base64_encode($img['content']),
                'isInline'     => true,
                'contentId'    => $img['cid'],
            ];
        }

        // Regular file attachments
        foreach ($fileAttachments as $att) {
            $graphAttachments[] = [
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'name'         => $att['name'],
                'contentType'  => $att['mime'],
                'contentBytes' => base64_encode($att['content']),
                'isInline'     => false,
            ];
        }

        if (!empty($graphAttachments)) {
            $message['attachments'] = $graphAttachments;
        }

        $response = Http::withToken($accessToken)
            ->post("{$baseUrl}/me/sendMail", [
                'message'         => $message,
                'saveToSentItems' => true,
            ]);

        if ($response->successful()) {
            Log::info('CustomerEmailService: Microsoft Graph send OK', [
                'to'            => $toEmail,
                'files'         => count($fileAttachments),
                'inline_images' => count($inlineImages),
            ]);
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
