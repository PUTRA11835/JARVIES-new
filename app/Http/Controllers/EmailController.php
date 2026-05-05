<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StagingTicket;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    // =========================================================================
    // GRAPH API HELPERS
    // =========================================================================

    /**
     * Ambil OAuth2 access token via client credentials flow.
     */
    private function getAccessToken(): string
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

    private function graphGet(string $path, array $query = []): array
    {
        $token   = $this->getAccessToken();
        $baseUrl = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');

        $response = Http::withToken($token)->get("{$baseUrl}{$path}", $query);

        if (!$response->successful()) {
            throw new \RuntimeException("Graph GET {$path} gagal: " . $response->body());
        }

        return $response->json();
    }

    private function graphPost(string $path, array $body): \Illuminate\Http\Client\Response
    {
        $token   = $this->getAccessToken();
        $baseUrl = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');

        $response = Http::withToken($token)->post("{$baseUrl}{$path}", $body);

        if (!$response->successful()) {
            throw new \RuntimeException("Graph POST {$path} gagal: " . $response->body());
        }

        return $response;
    }

    private function graphPatch(string $path, array $body): void
    {
        $token   = $this->getAccessToken();
        $baseUrl = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');

        Http::withToken($token)->patch("{$baseUrl}{$path}", $body);
    }

    /** Strips quoted-reply elements from HTML and returns plain text. Used for ticket messages. */
    private function extractReplyBody(string $html): string
    {
        if (empty($html)) return '';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(
            '<html><head><meta charset="utf-8"/></head><body>' .
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') .
            '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        $this->removeQuotedNodes($dom);

        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        $text = $bodyNode ? trim($bodyNode->textContent) : strip_tags($html);
        $text = preg_replace('/^On .{5,200}wrote:\s*$/m', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Strips quoted-reply elements from HTML and returns the remaining inner HTML.
     * Handles the case where Graph API returns a complete HTML document (with <html><body> tags).
     */
    private function extractHtmlBody(string $html): string
    {
        if (empty($html)) return '';

        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Graph API often returns a full HTML document — load it directly.
        // Only wrap in a shell when the content is a fragment (no <body> tag).
        $encoded = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        if (stripos($html, '<body') !== false) {
            @$dom->loadHTML($encoded, LIBXML_NOERROR | LIBXML_NOWARNING);
        } else {
            @$dom->loadHTML(
                '<html><head><meta charset="utf-8"/></head><body>' . $encoded . '</body></html>',
                LIBXML_NOERROR | LIBXML_NOWARNING
            );
        }

        $this->removeQuotedNodes($dom);

        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        if (!$bodyNode) return strip_tags($html);

        $result = '';
        foreach ($bodyNode->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    private function removeQuotedNodes(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $selectors = [
            '//blockquote',
            '//*[contains(@class,"gmail_quote")]',
            '//*[contains(@class,"yahoo_quoted")]',
            '//*[contains(@class,"moz-cite-prefix")]',
            '//*[@id="divRplyFwdMsg"]',
            '//*[contains(@class,"OutlookMessageHeader")]',
            '//*[contains(@class,"x_gmail_quote")]',
        ];
        foreach ($selectors as $sel) {
            foreach (iterator_to_array($xpath->query($sel)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    // =========================================================================
    // PUBLIC ENDPOINTS
    // =========================================================================

    /**
     * Ambil daftar pesan terbaru dari inbox (untuk debug / preview).
     */
    public function inbox()
    {
        $email = env('MS_SENDER_EMAIL');

        $data = $this->graphGet("/users/{$email}/mailFolders/inbox/messages", [
            '$top'     => 20,
            '$orderby' => 'receivedDateTime desc',
            '$select'  => 'id,subject,from,toRecipients,receivedDateTime,bodyPreview,internetMessageId,conversationId,isRead',
        ]);

        return response()->json($data['value'] ?? []);
    }

    /**
     * Kirim email baru via Graph API.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $sender = env('MS_SENDER_EMAIL');

        $this->graphPost("/users/{$sender}/sendMail", [
            'message' => [
                'subject' => $data['subject'],
                'body'    => ['contentType' => 'Text', 'content' => $data['body']],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $data['to']]],
                ],
            ],
            'saveToSentItems' => true,
        ]);

        return response()->json(['status' => 'sent']);
    }

    /**
     * Balas email via Graph API.
     * Jika ada in_reply_to (internetMessageId), gunakan endpoint /reply Graph.
     * Jika tidak ditemukan, fallback ke sendMail biasa.
     */
    public function reply(Request $request)
    {
        $data = $request->validate([
            'to'          => ['required', 'email'],
            'subject'     => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string'],
            'in_reply_to' => ['nullable', 'string'],
        ]);

        $sender  = env('MS_SENDER_EMAIL');
        $subject = stripos($data['subject'], 're:') !== 0 ? 'Re: ' . $data['subject'] : $data['subject'];

        if (!empty($data['in_reply_to'])) {
            try {
                $result = $this->graphGet("/users/{$sender}/messages", [
                    '$filter' => "internetMessageId eq '" . addslashes($data['in_reply_to']) . "'",
                    '$select' => 'id',
                    '$top'    => 1,
                ]);

                if (!empty($result['value'][0]['id'])) {
                    $this->graphPost(
                        "/users/{$sender}/messages/{$result['value'][0]['id']}/reply",
                        ['comment' => $data['body']]
                    );
                    return response()->json(['status' => 'replied']);
                }
            } catch (\Exception $e) {
                Log::warning('EmailController@reply: reply via Graph gagal, fallback ke sendMail', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: kirim sebagai email baru
        $this->graphPost("/users/{$sender}/sendMail", [
            'message' => [
                'subject' => $subject,
                'body'    => ['contentType' => 'Text', 'content' => $data['body']],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $data['to']]],
                ],
            ],
            'saveToSentItems' => true,
        ]);

        return response()->json(['status' => 'replied']);
    }

    /**
     * Proses inbox: buat tiket baru dari email baru, atau tambah pesan ke tiket yang ada.
     * Endpoint ini dipanggil oleh scheduler (email:process-inbox).
     */
    public function processInbox(Request $request)
    {
        try {
            $sender = env('MS_SENDER_EMAIL');

            // Ambil email yang belum dibaca
            $data = $this->graphGet("/users/{$sender}/mailFolders/inbox/messages", [
                '$filter'  => 'isRead eq false',
                '$orderby' => 'receivedDateTime asc',
                '$top'     => 50,
                '$select'  => 'id,subject,from,receivedDateTime,body,internetMessageId,conversationId',
            ]);

            $processed = 0;
            $skipped   = 0;
            $errors    = [];

            foreach ($data['value'] ?? [] as $msg) {
                try {
                    $graphMsgId     = $msg['id'];
                    $subject        = $msg['subject'] ?? '(no subject)';
                    $fromEmail      = $msg['from']['emailAddress']['address'] ?? null;
                    $fromName       = $msg['from']['emailAddress']['name'] ?? $fromEmail;
                    $rawBodyContent = $msg['body']['content'] ?? '';
                    $internetMsgId  = $msg['internetMessageId'] ?? null;
                    $conversationId = $msg['conversationId'] ?? null;

                    Log::info('[INBOX] Processing message', [
                        'graph_msg_id'     => $graphMsgId,
                        'internet_msg_id'  => $internetMsgId,
                        'conversation_id'  => $conversationId,
                        'subject'          => $subject,
                        'from'             => $fromEmail,
                        'body_content_type'=> $msg['body']['contentType'] ?? 'unknown',
                        'body_length'      => strlen($rawBodyContent),
                        'body_preview'     => substr(strip_tags($rawBodyContent), 0, 200),
                        'has_body_tag'     => stripos($rawBodyContent, '<body') !== false,
                    ]);

                    $body           = $this->extractReplyBody($rawBodyContent);
                    // Fallback: if stripping quoted content left nothing, use the full stripped text
                    if ($body === '') {
                        $body = trim(strip_tags($rawBodyContent));
                    }

                    // Cari tiket terkait:
                    // 0. Cek staging.email_thread_id → tiket yang sudah diapprove dari staging
                    // 1. Cek conversationId (email thread)
                    // 2. Cek internetMessageId di ticket_message
                    // 3. Cek ticket number di subject [TKT-XXXXX] — mencegah duplicate
                    //    saat customer buat tiket via web lalu mengirim email
                    $ticket = null;
                    if ($conversationId) {
                        // 0. Cek via staging.email_thread_id (untuk tiket dari web form)
                        $stagingMatch = StagingTicket::where('email_thread_id', $conversationId)
                            ->whereNotNull('ticket_id')
                            ->first();
                        if ($stagingMatch) {
                            $ticket = Ticket::find($stagingMatch->ticket_id);
                            // Sync email_thread_id ke ticket agar lookup berikutnya lebih cepat
                            if ($ticket && !$ticket->email_thread_id) {
                                $ticket->update(['email_thread_id' => $conversationId]);
                            }
                        }
                    }
                    if (!$ticket && $conversationId) {
                        // 1. Cek conversationId langsung di tickets
                        $ticket = Ticket::where('email_thread_id', $conversationId)->first();
                    }
                    if (!$ticket && $internetMsgId) {
                        // 2. Cek internetMessageId di ticket_message
                        $ticket = Ticket::whereHas('messages', function ($q) use ($internetMsgId) {
                            $q->where('email_message_id', $internetMsgId);
                        })->first();
                    }
                    if (!$ticket && preg_match('/\[([A-Z0-9\-]+)\]/', $subject, $matches)) {
                        // 3. Cek ticket number di subject [TKT-XXXXX]
                        $parsedNumber = $matches[1];
                        $ticket = Ticket::where('ticket_number', $parsedNumber)->first();
                        // Update email_thread_id agar reply berikutnya otomatis terthread
                        if ($ticket && $conversationId && !$ticket->email_thread_id) {
                            $ticket->update(['email_thread_id' => $conversationId]);
                        }
                    }

                    $customer = Customer::where('email', $fromEmail)->first();

                    if ($ticket) {
                        // Cek duplikat: jangan simpan pesan yang sama dua kali
                        $alreadyExists = $internetMsgId
                            && TicketMessage::where('email_message_id', $internetMsgId)->exists();

                        if (!$alreadyExists) {
                            TicketMessage::create([
                                'ticket_id'           => $ticket->ticket_id,
                                'sender_type'         => $customer ? 'customer' : 'system',
                                'sender_id'           => $customer?->customer_id,
                                'sender_email'        => $fromEmail,
                                'sender_name'         => $fromName,
                                'message'             => strip_tags($body),
                                'is_internal_note'    => false,
                                'channel'             => 'email',
                                'email_message_id'    => $internetMsgId,
                                'email_in_reply_to'   => null,
                                'is_read_by_customer' => true,
                                'is_read_by_agent'    => false,
                            ]);

                            $ticket->update([
                                'last_customer_reply_at' => now(),
                                'last_message_at'        => now(),
                            ]);
                        }

                    } else {
                        // Cek apakah email ini sudah terhubung ke staging ticket via thread ID
                        // (dikirim dari JARVIES web form — jangan buat tiket duplikat)
                        if ($conversationId && StagingTicket::where('email_thread_id', $conversationId)->exists()) {
                            $this->graphPatch("/users/{$sender}/messages/{$graphMsgId}", ['isRead' => true]);
                            $processed++;
                            continue;
                        }

                        // Step 1: create staging ticket (no body yet — need ID first for proxy URLs)
                        $stagingTicket = StagingTicket::create([
                            'customer_id'        => $customer?->customer_id,
                            'description'        => $subject,
                            'status'             => 'unvalidated',
                            'channel'            => 'email',
                            'email_thread_id'    => $conversationId ?? $internetMsgId,
                            'email_message_id'   => $internetMsgId,  // Internet Message-ID header
                            'graph_message_id'   => $graphMsgId,     // Graph internal ID (for attachment proxy)
                            'submitted_by_email' => $fromEmail,
                        ]);

                        // Step 2: extract HTML body + fetch attachment metadata
                        $rawHtmlBody    = $this->extractHtmlBody($rawBodyContent);
                        $attachmentMeta = [];

                        Log::info('[INBOX] extractHtmlBody result', [
                            'staging_id'       => $stagingTicket->id,
                            'raw_body_length'  => strlen($rawBodyContent),
                            'has_body_tag'     => stripos($rawBodyContent, '<body') !== false,
                            'html_body_length' => strlen($rawHtmlBody),
                            'html_body_empty'  => trim(strip_tags($rawHtmlBody)) === '',
                            'html_preview'     => substr(strip_tags($rawHtmlBody), 0, 300),
                        ]);

                        try {
                            // contentId hanya ada di type microsoft.graph.fileAttachment —
                            // butuh OData cast, kalau tidak Graph return 400 BadRequest.
                            $attData = $this->graphGet("/users/{$sender}/messages/{$graphMsgId}/attachments", [
                                '$select' => 'id,name,contentType,size,isInline,microsoft.graph.fileAttachment/contentId',
                            ]);
                            foreach ($attData['value'] ?? [] as $att) {
                                $attachmentMeta[] = [
                                    'source'      => 'graph',
                                    'id'          => $att['id'],
                                    'name'        => $att['name'] ?? 'attachment',
                                    'contentType' => $att['contentType'] ?? 'application/octet-stream',
                                    'size'        => $att['size'] ?? 0,
                                    'isInline'    => (bool) ($att['isInline'] ?? false),
                                    'contentId'   => trim($att['contentId'] ?? '', '<>'),
                                ];
                            }
                        } catch (\Exception $attEx) {
                            Log::warning('processInbox: failed to fetch attachments', [
                                'staging_id' => $stagingTicket->id,
                                'error'      => $attEx->getMessage(),
                            ]);
                        }

                        Log::info('[INBOX] Attachments fetched', [
                            'staging_id'  => $stagingTicket->id,
                            'count'       => count($attachmentMeta),
                            'attachments' => array_map(fn($a) => [
                                'name'      => $a['name'],
                                'isInline'  => $a['isInline'],
                                'contentId' => $a['contentId'],
                                'size'      => $a['size'],
                            ], $attachmentMeta),
                        ]);

                        // email_body_html: keep cid: references intact — EcoSystem resolves them via previewBody
                        $emailBodyHtml = $rawHtmlBody;

                        // Step 3: for JARVIES display, rewrite cid: to proxy URLs (index-based, URL-safe)
                        $bodyHtml = preg_replace_callback(
                            '/src=["\']cid:([^"\']+)["\']/i',
                            function ($matches) use ($attachmentMeta, $stagingTicket) {
                                $cid = $matches[1];
                                foreach ($attachmentMeta as $idx => $att) {
                                    $attCid   = $att['contentId'];
                                    $attLocal = (string) strstr($attCid, '@', true) ?: $attCid;
                                    $cidLocal = (string) strstr($cid,    '@', true) ?: $cid;
                                    if ($attCid === $cid || $attLocal === $cidLocal) {
                                        return 'src="' . route('tickets.staging.graph.inline', [
                                            'id'    => $stagingTicket->id,
                                            'index' => $idx,
                                        ]) . '"';
                                    }
                                }
                                return $matches[0];
                            },
                            $rawHtmlBody
                        );

                        // Fallback: if body is empty after stripping quoted content, use full plain text
                        $bodyAfterCid = trim(strip_tags((string) $bodyHtml));
                        Log::info('[INBOX] After CID rewrite', [
                            'staging_id'        => $stagingTicket->id,
                            'body_after_cid_empty' => $bodyAfterCid === '',
                            'body_preview'      => substr($bodyAfterCid, 0, 300),
                        ]);

                        if ($bodyAfterCid === '') {
                            $plainText     = trim(strip_tags($rawBodyContent));
                            $bodyHtml      = nl2br(htmlspecialchars($plainText, ENT_QUOTES, 'UTF-8'));
                            $emailBodyHtml = $bodyHtml;
                            Log::info('[INBOX] Used plain-text fallback', [
                                'staging_id'   => $stagingTicket->id,
                                'plain_length' => strlen($plainText),
                                'plain_preview'=> substr($plainText, 0, 200),
                            ]);
                        }

                        $stagingTicket->update([
                            'body'             => $bodyHtml,       // JARVIES: cid: replaced with proxy URLs
                            'email_body_html'  => $emailBodyHtml,  // EcoSystem: cid: intact for previewBody
                            'attachment_names' => json_encode($attachmentMeta),
                            'has_attachments'  => count($attachmentMeta) > 0,
                        ]);

                        Log::info('[INBOX] Staging ticket updated', [
                            'staging_id'        => $stagingTicket->id,
                            'body_length'       => strlen((string) $bodyHtml),
                            'body_preview'      => substr(strip_tags((string) $bodyHtml), 0, 200),
                            'email_body_length' => strlen((string) $emailBodyHtml),
                            'attachment_names'  => $attachmentMeta,
                        ]);

                        Log::info('EmailController@processInbox: staging ticket created from email', [
                            'staging_id'  => $stagingTicket->id,
                            'from'        => $fromEmail,
                            'subject'     => $subject,
                            'attachments' => count($attachmentMeta),
                        ]);
                    }

                    // Tandai sebagai sudah dibaca di Graph
                    $this->graphPatch("/users/{$sender}/messages/{$graphMsgId}", ['isRead' => true]);
                    $processed++;

                } catch (\Exception $e) {
                    Log::error('EmailController@processInbox: error processing message', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = $e->getMessage();
                    $skipped++;
                }
            }

            // ── Proses SentItems: tangkap balasan email employee dari shared mailbox ──────
            // Ketika employee reply langsung dari Outlook (shared mailbox), email keluar
            // via MS_SENDER_EMAIL → masuk SentItems, bukan INBOX → tidak tertangkap loop di atas.
            $sentProcessed = 0;
            try {
                $sentCutoff = now()->subMinutes(30)->toIso8601String();
                $sentData   = $this->graphGet("/users/{$sender}/mailFolders/SentItems/messages", [
                    '$filter'  => "sentDateTime ge {$sentCutoff}",
                    '$orderby' => 'sentDateTime asc',
                    '$top'     => 50,
                    '$select'  => 'id,subject,toRecipients,sentDateTime,body,internetMessageId,conversationId',
                ]);

                foreach ($sentData['value'] ?? [] as $sentMsg) {
                    $sentInternetMsgId  = $sentMsg['internetMessageId'] ?? null;
                    $sentConversationId = $sentMsg['conversationId'] ?? null;
                    $sentBody           = $this->extractReplyBody($sentMsg['body']['content'] ?? '');

                    if (empty($sentConversationId) || strlen(trim($sentBody)) < 3) {
                        continue;
                    }

                    // Cari tiket terkait: cek ticket.email_thread_id, lalu staging.email_thread_id
                    $sentTicket = Ticket::where('email_thread_id', $sentConversationId)->first();

                    if (!$sentTicket) {
                        $stagingForSent = StagingTicket::where('email_thread_id', $sentConversationId)
                            ->whereNotNull('ticket_id')
                            ->first();
                        if ($stagingForSent) {
                            $sentTicket = Ticket::find($stagingForSent->ticket_id);
                            if ($sentTicket && !$sentTicket->email_thread_id) {
                                $sentTicket->update(['email_thread_id' => $sentConversationId]);
                            }
                        }
                    }

                    if (!$sentTicket) {
                        continue;
                    }

                    // Hindari duplikat
                    if ($sentInternetMsgId && TicketMessage::where('email_message_id', $sentInternetMsgId)->exists()) {
                        continue;
                    }

                    TicketMessage::create([
                        'ticket_id'           => $sentTicket->ticket_id,
                        'sender_type'         => 'employee',
                        'sender_id'           => null,
                        'sender_email'        => $sender,
                        'sender_name'         => 'Support Team',
                        'message'             => strip_tags($sentBody),
                        'is_internal_note'    => false,
                        'channel'             => 'email',
                        'email_message_id'    => $sentInternetMsgId,
                        'email_in_reply_to'   => null,
                        'is_read_by_customer' => false,
                        'is_read_by_agent'    => true,
                    ]);

                    $sentTicket->update(['last_message_at' => now()]);
                    $sentProcessed++;
                }
            } catch (\Exception $e) {
                Log::warning('EmailController@processInbox: sent items processing failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status'         => 'done',
                'processed'      => $processed,
                'sent_processed' => $sentProcessed,
                'skipped'        => $skipped,
                'errors'         => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('EmailController@processInbox: Graph API gagal', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to access inbox: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kirim email balasan untuk sebuah tiket (digunakan oleh TicketMessageController).
     *
     * Alur threading yang benar:
     * 1. Cari pesan asli via internetMessageId di mailbox
     * 2. createReply → Graph otomatis set In-Reply-To & References
     * 3. Patch body draft dengan HTML dari Quill
     * 4. Send draft
     *
     * Fallback ke sendMail HTML jika pesan asli tidak ditemukan.
     */
    public function sendTicketReply(
        string $toEmail,
        string $subject,
        string $body,
        ?string $inReplyTo = null
    ): void {
        $sender       = env('MS_SENDER_EMAIL');
        $replySubject = stripos($subject, 're:') !== 0 ? 'Re: ' . $subject : $subject;

        if ($inReplyTo) {
            try {
                // Cari pesan asli di mailbox menggunakan internetMessageId
                $result = $this->graphGet("/users/{$sender}/messages", [
                    '$filter' => "internetMessageId eq '" . addslashes($inReplyTo) . "'",
                    '$select' => 'id',
                    '$top'    => 1,
                ]);

                if (!empty($result['value'][0]['id'])) {
                    $originalId = $result['value'][0]['id'];

                    // 1. Buat draft reply — Graph otomatis isi In-Reply-To & References
                    $draft   = $this->graphPost("/users/{$sender}/messages/{$originalId}/createReply", []);
                    $draftId = $draft->json('id');

                    // 2. Update body draft dengan HTML dari Quill
                    $this->graphPatch("/users/{$sender}/messages/{$draftId}", [
                        'body' => ['contentType' => 'HTML', 'content' => $body],
                    ]);

                    // 3. Kirim draft
                    $this->graphPost("/users/{$sender}/messages/{$draftId}/send", []);
                    return;
                }
            } catch (\Exception $e) {
                Log::warning('EmailController@sendTicketReply: createReply gagal, fallback ke sendMail', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: sendMail dengan HTML body
        $this->graphPost("/users/{$sender}/sendMail", [
            'message' => [
                'subject' => $replySubject,
                'body'    => ['contentType' => 'HTML', 'content' => $body],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $toEmail]],
                ],
            ],
            'saveToSentItems' => true,
        ]);
    }
}
