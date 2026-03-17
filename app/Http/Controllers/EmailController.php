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
            throw new \RuntimeException('Gagal mendapatkan access token: ' . $response->body());
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

    /**
     * Ekstrak hanya bagian reply baru dari body email HTML.
     * Membuang quoted text (<blockquote>, gmail_quote, Outlook divider, dll).
     */
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

        $xpath = new \DOMXPath($dom);

        // Elemen yang mengandung quoted/previous messages — hapus semua
        $removeSelectors = [
            '//blockquote',                                   // RFC standard, semua klien
            '//*[contains(@class,"gmail_quote")]',            // Gmail
            '//*[contains(@class,"yahoo_quoted")]',           // Yahoo Mail
            '//*[contains(@class,"moz-cite-prefix")]',        // Thunderbird
            '//*[@id="divRplyFwdMsg"]',                       // Outlook Web
            '//*[contains(@class,"OutlookMessageHeader")]',   // Outlook Desktop
            '//*[contains(@class,"x_gmail_quote")]',          // Gmail via Outlook
        ];

        foreach ($removeSelectors as $selector) {
            foreach (iterator_to_array($xpath->query($selector)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Ambil teks bersih dari <body>
        $bodyNode = $dom->getElementsByTagName('body')->item(0);
        $text = $bodyNode ? trim($bodyNode->textContent) : strip_tags($html);

        // Hapus baris "On [date] ... wrote:" yang masih tersisa
        $text = preg_replace('/^On .{5,200}wrote:\s*$/m', '', $text);

        // Bersihkan baris kosong berlebihan
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
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
                    $body           = $this->extractReplyBody($msg['body']['content'] ?? '');
                    $internetMsgId  = $msg['internetMessageId'] ?? null;
                    $conversationId = $msg['conversationId'] ?? null;

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

                        // Semua email baru masuk ke staging_tickets dulu (perlu validasi EcoSystem)
                        // EcoSystem yang approve → buat ticket + ticket_message pertama dari staging.body
                        StagingTicket::create([
                            'customer_id'        => $customer?->customer_id,
                            'description'        => $subject,
                            'body'               => strip_tags($body),
                            'status'             => 'unvalidated',
                            'channel'            => 'email',
                            'email_thread_id'    => $conversationId ?? $internetMsgId,
                            'submitted_by_email' => $fromEmail,
                        ]);

                        Log::info('EmailController@processInbox: staging ticket created from email', [
                            'from'    => $fromEmail,
                            'subject' => $subject,
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
                'message' => 'Gagal mengakses inbox: ' . $e->getMessage(),
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
