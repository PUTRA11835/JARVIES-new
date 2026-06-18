<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\GraphRelayService;
use App\Services\StagingTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API Ticket Controller (Mobile - Customer Only)
 *
 * Mengelola tiket dan percakapan untuk customer di Flutter.
 * Logic diambil dari TicketController web dan disesuaikan untuk API.
 */
class TicketController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // TIKET
    // ─────────────────────────────────────────────────────────

    /**
     * GET /api/tickets
     * List semua tiket milik customer.
     * Query param opsional: ?status=open|in_progress|hold|closed|cancel
     */
    public function index(Request $request)
    {
        $user = $request->attributes->get('api_user');

        try {
            $query = Ticket::with(['employee.basicData', 'members.basicData'])
                ->where('customer_id', $user['id'])
                ->orderBy('created_at', 'desc');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $data = $query->get()->map(fn($t) => $this->formatTicket($t));

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@index', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to load tickets.'], 500);
        }
    }

    /**
     * GET /api/tickets/{id}
     * Detail satu tiket. Customer hanya bisa akses miliknya.
     */
    public function show(Request $request, $id)
    {
        $user = $request->attributes->get('api_user');

        try {
            $ticket = Ticket::with(['employee.basicData', 'members.basicData'])
                ->where('ticket_id', $id)
                ->where('customer_id', $user['id'])
                ->first();

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
            }

            return response()->json(['success' => true, 'data' => $this->formatTicketDetail($ticket)]);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@show', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to load ticket detail.'], 500);
        }
    }

    /**
     * POST /api/tickets
     * Buat tiket baru. Customer masuk ke staging (menunggu validasi admin).
     *
     * Body:
     *   description      (required)
     *   ticket_priority  (nullable: Low|Medium|High)
     *   body             (nullable, pesan awal)
     */
    public function store(Request $request)
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'description'     => 'required|string',
            'ticket_priority' => 'nullable|in:Low,Medium,High',
            'ticket_type'     => 'nullable|in:Incident,Service Request,Change Request,Consult',
            'body'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $service = new StagingTicketService();
            $staging = $service->createFromWeb(
                $validator->validated(),
                $user['id'],
                $user['email'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Ticket submitted successfully and is awaiting admin validation.',
                'data'    => [
                    'id'              => $staging->id,
                    'staging_ref'     => 'STG-' . $staging->id,
                    'description'     => $staging->description,
                    'ticket_priority' => $staging->ticket_priority,
                    'status'          => $staging->status,
                    'status_label'    => $staging->status_label,
                    'created_at'      => $staging->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@store', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create ticket.'], 500);
        }
    }

    /**
     * POST /api/tickets/submit-with-email
     * Buat tiket baru dengan alur lengkap:
     *   1. Kirim email ke customer via Microsoft Graph API
     *   2. POST ke EcoSystem /jarvies/staging-tickets dengan channel=email
     *
     * Endpoint ini untuk keperluan testing Postman — identik dengan alur web UI.
     *
     * Body (multipart/form-data):
     *   description      (required)
     *   ticket_priority  (nullable: Low|Medium|High|Very High)
     *   body             (nullable, isi pesan plain text)
     *   body_html        (nullable, isi pesan HTML dari Quill)
     *   cc_emails[]      (nullable, array email CC)
     *   attachments[]    (nullable, file upload)
     */
    public function storeWithEmail(Request $request)
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'description'     => 'required|string|max:5000',
            'ticket_priority' => 'nullable|in:Very High,High,Medium,Low',
            'ticket_type'     => 'nullable|in:Incident,Service Request,Change Request,Consult',
            'body'            => 'nullable|string',
            'body_html'       => 'nullable|string',
            'cc_emails'       => 'nullable|array|max:10',
            'cc_emails.*'     => 'email',
            'attachments'     => 'nullable|array',
            'attachments.*'   => 'file|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated   = $validator->validated();
        $senderName  = $user['company_name'] ?? $user['name'] ?? null;
        $senderEmail = $user['email'] ?? null;

        if (!$senderEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Your account does not have an email. Please contact the administrator.',
            ], 422);
        }

        $ccEmails = array_values(array_filter($validated['cc_emails'] ?? []));

        // Body HTML; fallback ke plain text
        $bodyHtml = $validated['body_html'] ?? $validated['body'] ?? '';
        if ($bodyHtml && !str_starts_with(ltrim($bodyHtml), '<')) {
            $bodyHtml = '<p>' . nl2br(htmlspecialchars($bodyHtml)) . '</p>';
        }

        // ── STEP 1: Ekstrak inline images dari body HTML (data URI → cid:) ──
        $emailInlineImages = [];
        $emailBodyHtml     = $bodyHtml;

        if ($emailBodyHtml) {
            preg_match_all(
                '/<img[^>]+src="(data:(image\/[a-zA-Z+\-]+);base64,([A-Za-z0-9+\/=\s]+))"[^>]*>/i',
                $emailBodyHtml,
                $imgMatches,
                PREG_SET_ORDER
            );
            foreach ($imgMatches as $i => $m) {
                $mime    = strtolower($m[2]);
                $content = base64_decode(preg_replace('/\s+/', '', $m[3]));
                $ext     = ltrim(strrchr($mime, '/'), '/');
                $ext     = str_replace(['+xml', '+'], ['', '-'], $ext) ?: 'png';
                $cid     = 'img-' . ($i + 1) . '@jarvies';
                $imgName = 'image-' . ($i + 1) . '.' . $ext;

                $emailInlineImages[] = [
                    'name'    => $imgName,
                    'content' => $content,
                    'mime'    => $mime,
                    'cid'     => $cid,
                ];
                $emailBodyHtml = str_replace($m[1], 'cid:' . $cid, $emailBodyHtml);
            }
        }

        // ── STEP 2: Baca file attachments dari request ───────────────────────
        $emailFileAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $emailFileAttachments[] = [
                    'name'    => $file->getClientOriginalName(),
                    'content' => file_get_contents($file->getRealPath()),
                    'mime'    => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        // ── STEP 3: Kirim email via GraphRelayService ────────────────────────
        $emailSubject = '[Pending Validation] ' . $validated['description'];

        $emailBody = '<p style="color:#555;margin-bottom:12px"><em>[New ticket from '
            . htmlspecialchars($senderName ?? 'Customer')
            . ' via Jarvies]</em></p>'
            . '<div style="margin-bottom:16px"><strong>Description:</strong>'
            . '<div style="margin-top:8px;padding:12px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:4px">'
            . ($emailBodyHtml ?: '<p><em>(No message)</em></p>')
            . '</div></div>';

        try {
            $graphService = new GraphRelayService();
            $emailResult  = $graphService->sendStandaloneEmail(
                $senderEmail,
                $emailSubject,
                $emailBody,
                $ccEmails,
                $emailInlineImages,
                $emailFileAttachments
            );
        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@storeWithEmail: Graph exception', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }

        if (!$emailResult) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again.',
            ], 500);
        }

        $internetMsgId = $emailResult['internet_message_id'];

        // ── STEP 4: POST ke EcoSystem /jarvies/staging-tickets ──────────────
        $multipart = [
            ['name' => 'description',         'contents' => $validated['description']],
            ['name' => 'customer_id',         'contents' => (string) $user['id']],
            ['name' => 'submitted_by_email',  'contents' => $senderEmail],
            ['name' => 'body',                'contents' => $emailBodyHtml ?: ''],
            ['name' => 'internet_message_id', 'contents' => $internetMsgId],
            ['name' => 'sender_name',         'contents' => $senderName ?? ''],
            ['name' => 'ticket_priority',     'contents' => $validated['ticket_priority'] ?? 'Medium'],
            ['name' => 'ticket_type',         'contents' => $validated['ticket_type'] ?? ''],
            ['name' => 'channel',             'contents' => 'email'],
        ];

        if (!empty($ccEmails)) {
            $multipart[] = [
                'name'     => 'cc_emails',
                'contents' => json_encode(array_map(
                    fn($email) => ['name' => $email, 'address' => $email],
                    $ccEmails
                )),
            ];
        }

        foreach ($emailFileAttachments as $file) {
            $multipart[] = [
                'name'     => 'attachments[]',
                'contents' => $file['content'],
                'filename' => $file['name'],
                'headers'  => ['Content-Type' => $file['mime']],
            ];
        }

        $ecoStatus = null;
        $ecoBody   = null;
        $ecoError  = null;

        try {
            $ecoResponse = Http::withHeaders(['X-Api-Key' => config('ecosystem.api_key')])
                ->asMultipart()
                ->timeout(20)
                ->post(config('ecosystem.url') . '/jarvies/staging-tickets', $multipart);

            $ecoStatus = $ecoResponse->status();
            $ecoBody   = $ecoResponse->body();

            if (!$ecoResponse->successful()) {
                Log::warning('Mobile API: storeWithEmail: EcoSystem API failed (non-critical)', [
                    'status' => $ecoStatus,
                    'body'   => $ecoBody,
                ]);
            }
        } catch (\Exception $apiEx) {
            $ecoError = $apiEx->getMessage();
            Log::warning('Mobile API: storeWithEmail: EcoSystem API exception (non-critical)', [
                'error' => $ecoError,
            ]);
        }

        return response()->json([
            'success'    => true,
            'staging'    => true,
            'email_sent' => true,
            'message'    => 'Ticket submitted successfully and is awaiting admin validation.',
            'debug_eco'  => [
                'url'    => config('ecosystem.url') . '/jarvies/staging-tickets',
                'status' => $ecoStatus,
                'body'   => $ecoBody,
                'error'  => $ecoError,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────
    // STAGING
    // ─────────────────────────────────────────────────────────

    /**
     * GET /api/tickets/staging
     * List staging tiket (menunggu / sudah divalidasi admin).
     */
    public function staging(Request $request)
    {
        $user = $request->attributes->get('api_user');

        try {
            $service  = new StagingTicketService();
            $stagings = $service->getByCustomer($user['id']);

            $data = $stagings->map(fn($s) => [
                'id'               => $s->id,
                'staging_ref'      => 'STG-' . $s->id,
                'description'      => $s->description,
                'ticket_priority'  => $s->ticket_priority,
                'status'           => $s->status,
                'status_label'     => $s->status_label,
                'rejection_reason' => $s->rejection_reason,
                'ticket_id'        => $s->ticket_id,
                'ticket_number'    => $s->ticket?->ticket_number,
                'created_at'       => $s->created_at?->toISOString(),
                'validated_at'     => $s->validated_at?->toISOString(),
            ]);

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@staging', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to load staging tickets.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // PESAN / PERCAKAPAN
    // ─────────────────────────────────────────────────────────

    /**
     * GET /api/tickets/{id}/messages
     * Pesan percakapan tiket. Internal note tidak ditampilkan ke customer.
     * Otomatis tandai pesan dari agent sebagai sudah dibaca.
     */
    public function messages(Request $request, $id)
    {
        $user = $request->attributes->get('api_user');

        try {
            $ticket = Ticket::where('ticket_id', $id)
                ->where('customer_id', $user['id'])
                ->first();

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
            }

            $messages = TicketMessage::where('ticket_id', $id)
                ->where('is_internal_note', false)
                ->with('attachments')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'id'          => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_name,
                    'message'     => $msg->message,
                    'attachments' => $msg->attachments
                        ->filter(fn($a) => !($a->is_inline ?? false))
                        ->map(fn($a) => [
                            'id'        => $a->id,
                            'file_name' => $a->file_name ?? $a->link_title ?? 'Attachment',
                            'type'      => $a->attachment_type,
                            'url'       => $a->url ?? $a->link_url,
                        ])->values(),
                    'created_at'  => $msg->created_at,
                ]);

            // Tandai pesan agent sebagai sudah dibaca
            TicketMessage::where('ticket_id', $id)
                ->where('sender_type', 'employee')
                ->where('is_read_by_customer', false)
                ->update(['is_read_by_customer' => true]);

            return response()->json(['success' => true, 'data' => $messages]);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@messages', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to load messages.'], 500);
        }
    }

    /**
     * POST /api/tickets/{id}/messages
     * Kirim pesan/reply ke tiket.
     *
     * Body:
     *   message (required, max 5000 karakter)
     */
    public function sendMessage(Request $request, $id)
    {
        $user = $request->attributes->get('api_user');

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Message cannot be empty.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $ticket = Ticket::where('ticket_id', $id)
                ->where('customer_id', $user['id'])
                ->first();

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
            }

            $msg = TicketMessage::create([
                'ticket_id'              => $ticket->ticket_id,
                'sender_type'            => 'customer',
                'sender_id'              => $user['id'],
                'sender_email'           => $user['email'] ?? null,
                'sender_name'            => $user['company_name'] ?? 'Customer',
                'message'                => $request->message,
                'is_internal_note'       => false,
                'channel'                => 'web',
                'is_read_by_customer'    => true,
                'is_read_by_agent'       => false,
            ]);

            // last_message_at / last_customer_reply_at tidak ada di schema ticket — skip update

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data'    => [
                    'id'          => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_name,
                    'message'     => $msg->message,
                    'created_at'  => $msg->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@sendMessage', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to send message.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────

    private function formatTicket(Ticket $t): array
    {
        return [
            'ticket_id'       => $t->ticket_id,
            'ticket_number'   => $t->ticket_number,
            'description'     => $t->description,
            'status'          => $t->status,
            'status_label'    => $t->status_label,
            'ticket_priority' => $t->ticket_priority,
            'priority_color'  => $t->priority_color,
            'start_date'      => $t->start_date,
            'end_date'        => $t->end_date,
            'man_days'        => $t->man_days,
            'employee'        => $t->employee ? [
                'employee_id'   => $t->employee->employee_id,
                'employee_name' => $t->employee->basicData?->first_name ?? 'Unknown',
            ] : null,
            'members'         => $t->members->map(fn($m) => [
                'employee_id'   => $m->employee_id,
                'employee_name' => $m->basicData?->first_name ?? 'Unknown',
            ]),
            'created_at'      => $t->created_at,
            'updated_at'      => $t->updated_at,
        ];
    }

    private function formatTicketDetail(Ticket $t): array
    {
        return array_merge($this->formatTicket($t), [
            'channel'    => $t->channel,
            'wait_close' => $t->wait_close,
        ]);
    }
}
