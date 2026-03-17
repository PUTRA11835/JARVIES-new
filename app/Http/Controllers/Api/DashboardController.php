<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API Dashboard Controller (Mobile - Customer Only)
 *
 * Menyediakan ringkasan data untuk halaman Home di Flutter.
 * Data dihitung langsung dari database (tidak pakai EcosystemApi).
 */
class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Statistik tiket customer + tiket terbaru.
     */
    public function index(Request $request)
    {
        $user       = $request->attributes->get('api_user');
        $customerId = $user['id'];

        try {
            $tickets = Ticket::where('customer_id', $customerId)->get();

            // Hitung per status
            $summary = [
                'total'       => $tickets->count(),
                'open'        => $tickets->where('status', 'open')->count(),
                'in_progress' => $tickets->where('status', 'in_progress')->count(),
                'hold'        => $tickets->where('status', 'hold')->count(),
                'closed'      => $tickets->where('status', 'closed')->count(),
                'cancel'      => $tickets->where('status', 'cancel')->count(),
            ];

            // Jumlah pesan belum dibaca dari agent
            $unreadMessages = TicketMessage::whereIn(
                    'ticket_id',
                    $tickets->pluck('ticket_id')
                )
                ->where('sender_type', 'employee')
                ->where('is_read_by_customer', false)
                ->where('is_internal_note', false)
                ->count();

            // 5 tiket terbaru
            $recentTickets = Ticket::with(['employee.basicData'])
                ->where('customer_id', $customerId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($t) => [
                    'ticket_id'       => $t->ticket_id,
                    'ticket_number'   => $t->ticket_number,
                    'description'     => $t->description,
                    'status'          => $t->status,
                    'status_label'    => $t->status_label,
                    'ticket_priority' => $t->ticket_priority,
                    'priority_color'  => $t->priority_color,
                    'employee_name'   => $t->employee?->basicData?->first_name ?? null,
                    'created_at'      => $t->created_at,
                ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'summary'         => $summary,
                    'unread_messages' => $unreadMessages,
                    'recent_tickets'  => $recentTickets,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile API: DashboardController@index error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard.',
            ], 500);
        }
    }
}
