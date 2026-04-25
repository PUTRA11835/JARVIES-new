<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim relay email FROM helpdesk (raditya) TO customer via Microsoft Graph API.
 *
 * Menggunakan email-first architecture:
 *   1. Buat draft — via createReply (jika thread ada) atau pesan baru (jika belum ada thread)
 *   2. PATCH draft: toRecipients = customer, body, subject  (fix bug createReply)
 *   3. Upload attachment ke draft
 *   4. Ambil internetMessageId SEBELUM send (ini konstanta antara draft ↔ Sent Items)
 *   5. Send draft → draft ID invalid, pesan pindah ke Sent Items dengan ID baru
 *   6. Cari Sent Items message via PHP matching, retry 3x dengan sleep(1s)
 *   7. Ambil Sent Items attachment IDs by filename (bukan draft att IDs)
 *   8. Return semua ID untuk disimpan ke DB
 *
 * Catatan Graph API:
 *   - `internetMessageHeaders` TIDAK boleh berisi 'In-Reply-To' (hanya X-* headers yang diizinkan)
 *   - Threading dilakukan via createReply pada pesan terakhir di conversationId yang sama
 *   - createReply BUG: toRecipients diset ke FROM (raditya sendiri) → wajib PATCH setelah createReply
 */
class GraphRelayService
{
    private string $graphBase;
    private string $sender;

    public function __construct()
    {
        $this->graphBase = rtrim(env('GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'), '/');
        $this->sender    = env('MS_SENDER_EMAIL', '');
    }

    // ─── Auth ─────────────────────────────────────────────────────────────────

    private function getAccessToken(): ?string
    {
        $tenantId     = env('MS_TENANT_ID');
        $clientId     = env('MS_CLIENT_ID');
        $clientSecret = env('MS_CLIENT_SECRET');

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$response->successful()) {
            Log::error('GraphRelayService: failed to get access token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('access_token');
    }

    // ─── Draft helpers ────────────────────────────────────────────────────────

    /**
     * Cari pesan terakhir dalam thread (conversationId) di semua folder raditya,
     * lalu panggil createReply untuk membuat draft reply yang terhubung ke thread.
     * Setelah createReply, PATCH toRecipients ke customer (fix bug Graph).
     *
     * @return string|null draftId jika berhasil, null jika gagal
     */
    private function createReplyDraft(
        string $token,
        string $conversationId,
        string $toEmail,
        string $subject,
        string $body,
        array $ccEmails = []
    ): ?string {
        // Cari pesan terakhir dalam conversationId.
        //
        // PENTING: Graph API tidak mendukung `$filter=conversationId eq 'xxx'`
        // dikombinasikan dengan `$orderby=receivedDateTime desc` — return 400
        // "InefficientFilter". Kalau error, silent fail → createReplyDraft return
        // null → fallback ke createNewDraft → email baru dengan conv baru →
        // Gmail pecah thread jadi kotak terpisah.
        //
        // Solusi: fetch semua pesan dalam conversation tanpa orderby, lalu pilih
        // yang terbaru di PHP. Prioritas pesan Inbox (customer-sourced) karena
        // createReply pada pesan inbound menghasilkan thread yang lebih konsisten.
        $searchRes = Http::withToken($token)->get(
            "{$this->graphBase}/users/{$this->sender}/messages",
            [
                '$filter' => "conversationId eq '{$conversationId}'",
                '$select' => 'id,receivedDateTime,sentDateTime,parentFolderId',
                '$top'    => 50,
            ]
        );

        if (!$searchRes->successful()) {
            Log::warning('GraphRelayService: conversation search failed', [
                'conversationId' => $conversationId,
                'status'         => $searchRes->status(),
                'body'           => $searchRes->body(),
            ]);
            return null;
        }

        $allMsgs = $searchRes->json('value') ?? [];
        if (empty($allMsgs)) {
            Log::warning('GraphRelayService: no message found for conversationId', [
                'conversationId' => $conversationId,
            ]);
            return null;
        }

        // Pilih latest: utamakan receivedDateTime (inbound email), fallback ke sentDateTime.
        // Sort desc by max(receivedDateTime, sentDateTime).
        usort($allMsgs, function ($a, $b) {
            $aTime = $a['receivedDateTime'] ?? $a['sentDateTime'] ?? '';
            $bTime = $b['receivedDateTime'] ?? $b['sentDateTime'] ?? '';
            return strcmp($bTime, $aTime); // desc
        });

        $lastMsgId = $allMsgs[0]['id'];

        // createReply membuat draft reply yang terhubung ke thread
        $replyRes = Http::withToken($token)->post(
            "{$this->graphBase}/users/{$this->sender}/messages/{$lastMsgId}/createReply",
            []
        );

        if (!$replyRes->successful()) {
            Log::warning('GraphRelayService: createReply failed', [
                'status' => $replyRes->status(),
                'body'   => $replyRes->body(),
            ]);
            return null;
        }

        $draftId = $replyRes->json('id');

        // WAJIB: PATCH toRecipients ke customer
        // Bug Graph: createReply menyetel toRecipients = raditya (pengirim asli), bukan customer
        //
        // SUBJECT TIDAK DI-PATCH pada reply draft.
        // Alasan: createReply auto-set subject "Re: {original}" yang preserve conversationId
        // di Exchange. Jika kita PATCH subject ke format berbeda ("Ticket #XXXX: desc"),
        // Exchange deteksi subject change drastis → generate conversationId baru → Gmail
        // memecah thread jadi email terpisah. Biarkan "Re: ..." default agar thread tetap
        // satu. ccRecipients selalu di-set eksplisit (bisa [] untuk hapus pre-populated).
        $patchPayload = [
            'body'         => ['contentType' => 'HTML', 'content' => $body],
            'toRecipients' => [
                ['emailAddress' => ['address' => $toEmail]],
            ],
            'ccRecipients' => array_map(
                fn($cc) => ['emailAddress' => ['address' => $cc]],
                $ccEmails
            ),
        ];

        Http::withToken($token)->patch(
            "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}",
            $patchPayload
        );

        return $draftId;
    }

    /**
     * Buat draft baru (tidak terhubung ke thread manapun).
     * Digunakan saat belum ada email_thread_id atau createReply gagal.
     *
     * @return string|null draftId
     */
    private function createNewDraft(
        string $token,
        string $toEmail,
        string $subject,
        string $body,
        array $ccEmails = []
    ): ?string {
        $payload = [
            'subject'      => $subject,
            'body'         => ['contentType' => 'HTML', 'content' => $body],
            'toRecipients' => [
                ['emailAddress' => ['address' => $toEmail]],
            ],
        ];

        if (!empty($ccEmails)) {
            $payload['ccRecipients'] = array_map(
                fn($cc) => ['emailAddress' => ['address' => $cc]],
                $ccEmails
            );
        }

        $draftRes = Http::withToken($token)->post(
            "{$this->graphBase}/users/{$this->sender}/messages",
            $payload
        );

        if (!$draftRes->successful()) {
            Log::error('GraphRelayService: create new draft failed', [
                'status' => $draftRes->status(),
                'body'   => $draftRes->body(),
            ]);
            return null;
        }

        return $draftRes->json('id');
    }

    // ─── Standalone email (untuk staging / notifikasi tanpa thread) ──────────

    /**
     * Kirim email baru (bukan reply ke thread yang ada) dari helpdesk ke customer.
     * Digunakan saat staging ticket pertama kali dibuat — belum ada conversationId.
     *
     * @param string   $toEmail          Email tujuan (customer)
     * @param string   $subject          Subject email
     * @param string   $htmlBody         Body HTML (cid: references untuk inline image)
     * @param array    $ccEmails         CC recipients
     * @param array    $inlineImages     [ ['name'=>, 'content'=>(binary), 'mime'=>, 'cid'=>] ]
     * @param array    $fileAttachments  [ ['name'=>, 'content'=>(binary), 'mime'=>] ]
     * @param int|null $logRefId         Referensi ID untuk logging (staging ID dsb.)
     *
     * @return array|null  null jika gagal. Array jika berhasil:
     *   [ 'internet_message_id'=>, 'conversation_id'=>, 'attachments'=>[] ]
     */
    public function sendStandaloneEmail(
        string $toEmail,
        string $subject,
        string $htmlBody,
        array $ccEmails = [],
        array $inlineImages = [],
        array $fileAttachments = [],
        ?int $logRefId = null
    ): ?array {
        if (empty($this->sender)) {
            Log::error('GraphRelayService@sendStandaloneEmail: MS_SENDER_EMAIL not configured');
            return null;
        }

        if (empty($toEmail)) {
            Log::warning('GraphRelayService@sendStandaloneEmail: toEmail is empty', ['ref_id' => $logRefId]);
            return null;
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $draftId = $this->createNewDraft($token, $toEmail, $subject, $htmlBody, $ccEmails);
        if (!$draftId) {
            return null;
        }

        // ─── Upload inline images ──────────────────────────────────────────────
        $uploadedInline = [];
        foreach ($inlineImages as $img) {
            $res = Http::withToken($token)->post(
                "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/attachments",
                [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => $img['name'],
                    'contentType'  => $img['mime'],
                    'contentBytes' => base64_encode($img['content']),
                    'isInline'     => true,
                    'contentId'    => $img['cid'],
                ]
            );
            $uploadedInline[] = [
                'name'      => $img['name'],
                'mime'      => $img['mime'],
                'size'      => strlen($img['content']),
                'cid'       => $img['cid'],
                'is_inline' => true,
                'uploaded'  => $res->successful(),
            ];
        }

        // ─── Upload file attachments ───────────────────────────────────────────
        $uploadedFiles = [];
        foreach ($fileAttachments as $file) {
            $res = Http::withToken($token)->post(
                "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/attachments",
                [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => $file['name'],
                    'contentType'  => $file['mime'],
                    'contentBytes' => base64_encode($file['content']),
                    'isInline'     => false,
                ]
            );
            $uploadedFiles[] = [
                'name'      => $file['name'],
                'mime'      => $file['mime'],
                'size'      => strlen($file['content']),
                'cid'       => null,
                'is_inline' => false,
                'uploaded'  => $res->successful(),
            ];
        }

        // ─── Fetch internetMessageId + conversationId SEBELUM send ────────────
        $infoRes = Http::withToken($token)->get(
            "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}",
            ['$select' => 'internetMessageId,conversationId']
        );
        $internetMsgId  = $infoRes->json('internetMessageId');
        $conversationId = $infoRes->json('conversationId');

        // ─── Send ──────────────────────────────────────────────────────────────
        $sendRes = Http::withToken($token)->post(
            "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/send",
            []
        );

        if (!$sendRes->successful() && $sendRes->status() !== 202) {
            Log::error('GraphRelayService@sendStandaloneEmail: send failed', [
                'ref_id' => $logRefId,
                'status' => $sendRes->status(),
                'body'   => $sendRes->body(),
            ]);
            return null;
        }

        Log::info('GraphRelayService@sendStandaloneEmail: sent', [
            'ref_id'          => $logRefId,
            'to'              => $toEmail,
            'inline'          => count($uploadedInline),
            'files'           => count($uploadedFiles),
            'internet_msg_id' => $internetMsgId,
            'conversation_id' => $conversationId,
        ]);

        return [
            'internet_message_id' => $internetMsgId,
            'conversation_id'     => $conversationId,
            'attachments'         => array_merge($uploadedInline, $uploadedFiles),
        ];
    }

    // ─── Main method ──────────────────────────────────────────────────────────

    /**
     * Kirim relay email dari helpdesk ke customer, lengkap dengan attachment.
     *
     * @param Ticket      $ticket          Objek tiket (butuh ticket_number, description, email_thread_id)
     * @param string      $toEmail         Email customer (tujuan pengiriman)
     * @param string      $senderName      Nama customer (untuk wrapper pesan)
     * @param string      $htmlBody        Isi pesan HTML dengan cid: references untuk inline image
     * @param string|null $inReplyTo       Tidak digunakan (disimpan untuk kompatibilitas signature)
     * @param array       $inlineImages    [ ['name'=>, 'content'=>(binary), 'mime'=>, 'cid'=>] ]
     * @param array       $fileAttachments [ ['name'=>, 'content'=>(binary), 'mime'=>] ]
     *
     * @return array|null  null jika gagal. Array jika berhasil:
     *   [
     *     'internet_message_id' => '<xxx@PROD.OUTLOOK.COM>',
     *     'graph_message_id'    => 'AAMkADg...SENTITEMS...',   // null jika Sent Items tidak ditemukan
     *     'conversation_id'     => 'AQQkADg...',
     *     'attachments' => [
     *       [
     *         'name'         => 'file.pdf',
     *         'mime'         => 'application/pdf',
     *         'size'         => 102400,
     *         'cid'          => null,          // atau 'img-1@jarvies' untuk inline
     *         'is_inline'    => false,
     *         'uploaded'     => true,
     *         'graph_att_id' => 'AAMkADg...ATTID-SENT...',   // null jika tidak ditemukan
     *       ],
     *     ],
     *   ]
     */
    public function sendRelayEmail(
        Ticket $ticket,
        string $toEmail,
        string $senderName,
        string $htmlBody,
        ?string $inReplyTo,
        array $inlineImages = [],
        array $fileAttachments = [],
        array $ccEmails = []
    ): ?array {
        if (empty($this->sender)) {
            Log::error('GraphRelayService: MS_SENDER_EMAIL not configured');
            return null;
        }

        if (empty($toEmail)) {
            Log::error('GraphRelayService: toEmail is empty', ['ticket_id' => $ticket->ticket_id]);
            return null;
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $subject = 'Ticket #' . ($ticket->ticket_number ?? $ticket->ticket_id)
            . ': ' . mb_substr($ticket->description ?? '', 0, 80);

        // Bungkus pesan dengan atribusi pengirim (format sama dengan EcoSystem relay)
        $wrappedBody = '<p><em>[Pesan dari ' . htmlspecialchars($senderName) . ' via Jarvies]</em></p>'
            . $htmlBody;

        // ─── Step 1: Buat draft ───────────────────────────────────────────────
        // Jika thread sudah ada → createReply (threading otomatis lewat Graph)
        // Jika belum ada thread → buat pesan baru
        $draftId = null;

        if ($ticket->email_thread_id) {
            $draftId = $this->createReplyDraft(
                $token,
                $ticket->email_thread_id,
                $toEmail,
                $subject,
                $wrappedBody,
                $ccEmails
            );
        }

        if (!$draftId) {
            // Fallback: buat draft baru (belum ada thread atau createReply gagal)
            $draftId = $this->createNewDraft($token, $toEmail, $subject, $wrappedBody, $ccEmails);
        }

        if (!$draftId) {
            return null;
        }

        // ─── Step 2: Upload inline images ke draft ────────────────────────────
        $uploadedInline = [];
        foreach ($inlineImages as $img) {
            $res = Http::withToken($token)->post(
                "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/attachments",
                [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => $img['name'],
                    'contentType'  => $img['mime'],
                    'contentBytes' => base64_encode($img['content']),
                    'isInline'     => true,
                    'contentId'    => $img['cid'],
                ]
            );
            $uploadedInline[] = [
                'name'      => $img['name'],
                'mime'      => $img['mime'],
                'size'      => strlen($img['content']),
                'cid'       => $img['cid'],
                'is_inline' => true,
                'uploaded'  => $res->successful(),
            ];
        }

        // ─── Step 3: Upload file attachments ke draft ─────────────────────────
        $uploadedFiles = [];
        foreach ($fileAttachments as $file) {
            $res = Http::withToken($token)->post(
                "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/attachments",
                [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => $file['name'],
                    'contentType'  => $file['mime'],
                    'contentBytes' => base64_encode($file['content']),
                    'isInline'     => false,
                ]
            );
            $uploadedFiles[] = [
                'name'      => $file['name'],
                'mime'      => $file['mime'],
                'size'      => strlen($file['content']),
                'cid'       => null,
                'is_inline' => false,
                'uploaded'  => $res->successful(),
            ];
        }

        $allUploaded = array_merge($uploadedInline, $uploadedFiles);

        // ─── Step 4: Ambil internetMessageId SEBELUM send ────────────────────
        // internetMessageId adalah satu-satunya konstanta antara draft dan Sent Items.
        $infoRes = Http::withToken($token)->get(
            "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}",
            ['$select' => 'internetMessageId,conversationId']
        );
        $internetMsgId  = $infoRes->json('internetMessageId');
        $conversationId = $infoRes->json('conversationId');

        // ─── Step 5: Send draft ───────────────────────────────────────────────
        $sendRes = Http::withToken($token)->post(
            "{$this->graphBase}/users/{$this->sender}/messages/{$draftId}/send",
            []
        );

        // Graph mengembalikan 202 Accepted saat berhasil
        if (!$sendRes->successful() && $sendRes->status() !== 202) {
            Log::error('GraphRelayService: send draft failed', [
                'ticket_id' => $ticket->ticket_id,
                'status'    => $sendRes->status(),
                'body'      => $sendRes->body(),
            ]);
            return null;
        }

        Log::info('GraphRelayService: draft sent', [
            'ticket_id'       => $ticket->ticket_id,
            'to'              => $toEmail,
            'internet_msg_id' => $internetMsgId,
            'inline'          => count($uploadedInline),
            'files'           => count($uploadedFiles),
        ]);

        // ─── Step 6: Cari Sent Items message (retry 3x, sleep 1s) ───────────
        // Draft ID tidak valid setelah send. Graph butuh beberapa detik untuk index.
        // Cari via PHP matching (OData filter pada internetMessageId tidak reliable).
        $sentMsgId = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            if ($attempt > 1) {
                sleep(1);
            }
            $sentSearch = Http::withToken($token)->get(
                "{$this->graphBase}/users/{$this->sender}/mailFolders/SentItems/messages",
                ['$orderby' => 'sentDateTime desc', '$select' => 'id,internetMessageId', '$top' => 20]
            );
            foreach ($sentSearch->json('value') ?? [] as $msg) {
                if ($msg['internetMessageId'] === $internetMsgId) {
                    $sentMsgId = $msg['id'];
                    break 2;
                }
            }
        }

        // Jika Sent Items tidak ditemukan (edge case), return partial.
        // AttachmentController sudah punya auto-recovery fallback.
        if (!$sentMsgId) {
            Log::warning('GraphRelayService: Sent Items not found after 3 attempts', [
                'ticket_id'       => $ticket->ticket_id,
                'internet_msg_id' => $internetMsgId,
            ]);
            return [
                'internet_message_id' => $internetMsgId,
                'graph_message_id'    => null,
                'conversation_id'     => $conversationId,
                'attachments'         => array_map(fn($a) => array_merge($a, ['graph_att_id' => null]), $allUploaded),
            ];
        }

        // ─── Step 7: Ambil Sent Items attachment IDs by filename ─────────────
        // Attachment ID dari draft TIDAK VALID setelah send — harus ambil ulang.
        $sentAttRes = Http::withToken($token)->get(
            "{$this->graphBase}/users/{$this->sender}/messages/{$sentMsgId}/attachments",
            ['$select' => 'id,name']
        );

        $sentAttMap = [];
        foreach ($sentAttRes->json('value') ?? [] as $att) {
            $sentAttMap[strtolower($att['name'])] = $att['id'];
        }

        $attachmentsResult = [];
        foreach ($allUploaded as $file) {
            $sentAttId = $sentAttMap[strtolower($file['name'])] ?? null;
            $attachmentsResult[] = array_merge($file, ['graph_att_id' => $sentAttId]);
        }

        return [
            'internet_message_id' => $internetMsgId,
            'graph_message_id'    => $sentMsgId,
            'conversation_id'     => $conversationId,
            'attachments'         => $attachmentsResult,
        ];
    }
}
