<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\StagingTicketService;
use Illuminate\Http\Request;
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

            return response()->json(['success' => false, 'message' => 'Gagal memuat tiket.'], 500);
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
                return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan.'], 404);
            }

            return response()->json(['success' => true, 'data' => $this->formatTicketDetail($ticket)]);

        } catch (\Exception $e) {
            Log::error('Mobile API: TicketController@show', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Gagal memuat detail tiket.'], 500);
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
            'body'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
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
                'message' => 'Tiket berhasil dikirim dan sedang menunggu validasi admin.',
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

            return response()->json(['success' => false, 'message' => 'Gagal membuat tiket.'], 500);
        }
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

            return response()->json(['success' => false, 'message' => 'Gagal memuat staging tiket.'], 500);
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
                return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan.'], 404);
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

            return response()->json(['success' => false, 'message' => 'Gagal memuat pesan.'], 500);
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
                'message' => 'Pesan tidak boleh kosong.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $ticket = Ticket::where('ticket_id', $id)
                ->where('customer_id', $user['id'])
                ->first();

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan.'], 404);
            }

            $msg = TicketMessage::create([
                'ticket_id'              => $ticket->ticket_id,
                'sender_type'            => 'customer',
                'sender_id'              => $user['id'],
                'sender_email'           => $user['email'] ?? null,
                'sender_name'            => $user['company_name'] ?? 'Customer',
                'message'                => $request->message,
                'is_internal_note'       => false,
                'channel'                => 'mobile',
                'is_read_by_customer'    => true,
                'is_read_by_agent'       => false,
            ]);

            $ticket->update([
                'last_message_at'        => now(),
                'last_customer_reply_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim.',
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

            return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan.'], 500);
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
            'jarvies_status' => $t->jarvies_status,
            'channel'        => $t->channel,
            'wait_close'     => $t->wait_close,
        ]);
    }
}
