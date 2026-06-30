<?php

namespace App\Http\Controllers;

use App\Models\StagingTicket;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user       = session('user');
        $customerId = (int) ($user['id'] ?? 0);

        // ── Ticket counts (real data from shared DB) ────────────────────────
        $statusCounts = Ticket::where('customer_id', $customerId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END)                    as closed,
                SUM(CASE WHEN status = 'inprocess' THEN 1 ELSE 0 END)                 as in_process,
                SUM(CASE WHEN status = 'waiting_on_customer' THEN 1 ELSE 0 END)       as waiting_on_customer,
                SUM(CASE WHEN status = 'waiting_to_confirmation' THEN 1 ELSE 0 END)   as waiting_to_confirmation,
                SUM(CASE WHEN status = 'waiting_on_3rd_party' THEN 1 ELSE 0 END)      as waiting_on_3rd_party,
                SUM(CASE WHEN status = 'hold' THEN 1 ELSE 0 END)                      as hold,
                SUM(CASE WHEN status != 'closed' AND status != 'cancelled' THEN 1 ELSE 0 END) as open
            ")
            ->first();

        $pendingCount = StagingTicket::where('customer_id', $customerId)
            ->where('status', 'unvalidated')
            ->count();

        $stats = [
            'total'                   => (int) ($statusCounts->total                   ?? 0),
            'open'                    => (int) ($statusCounts->open                    ?? 0),
            'closed'                  => (int) ($statusCounts->closed                  ?? 0),
            'in_process'              => (int) ($statusCounts->in_process              ?? 0),
            'waiting_on_customer'     => (int) ($statusCounts->waiting_on_customer     ?? 0),
            'waiting_to_confirmation' => (int) ($statusCounts->waiting_to_confirmation ?? 0),
            'waiting_on_3rd_party'    => (int) ($statusCounts->waiting_on_3rd_party    ?? 0),
            'hold'                    => (int) ($statusCounts->hold                    ?? 0),
            'pending_approval'        => $pendingCount,
        ];

        // ── Recent tickets (last 5) ─────────────────────────────────────────
        $recentTickets = Ticket::where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['ticket_id', 'ticket_number', 'description', 'status', 'ticket_priority', 'created_at']);

        // ── Ticket trend — submissions per day for the last 30 days ─────────
        $trendRows = DB::table('ticket')
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        // Build complete 30-day series (fill zeros for missing days)
        $trendLabels = [];
        $trendData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $date          = now()->subDays($i)->format('Y-m-d');
            $trendLabels[] = now()->subDays($i)->format('d M');
            $trendData[]   = (int) ($trendRows[$date] ?? 0);
        }

        return view('dashboard', compact('user', 'stats', 'recentTickets', 'trendLabels', 'trendData'));
    }
}
