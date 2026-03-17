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
                SUM(CASE WHEN jarvies_status = 'closed' THEN 1 ELSE 0 END)            as closed,
                SUM(CASE WHEN jarvies_status = 'in process' THEN 1 ELSE 0 END)        as in_process,
                SUM(CASE WHEN jarvies_status = 'author action' THEN 1 ELSE 0 END)     as author_action,
                SUM(CASE WHEN jarvies_status = 'proposed solution' THEN 1 ELSE 0 END) as proposed_solution,
                SUM(CASE WHEN jarvies_status != 'closed' THEN 1 ELSE 0 END)           as open
            ")
            ->first();

        $pendingCount = StagingTicket::where('customer_id', $customerId)
            ->where('status', 'unvalidated')
            ->count();

        $stats = [
            'total'             => (int) ($statusCounts->total             ?? 0),
            'open'              => (int) ($statusCounts->open              ?? 0),
            'closed'            => (int) ($statusCounts->closed            ?? 0),
            'in_process'        => (int) ($statusCounts->in_process        ?? 0),
            'author_action'     => (int) ($statusCounts->author_action     ?? 0),
            'proposed_solution' => (int) ($statusCounts->proposed_solution ?? 0),
            'pending_approval'  => $pendingCount,
        ];

        // ── Recent tickets (last 5) ─────────────────────────────────────────
        $recentTickets = Ticket::where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['ticket_id', 'ticket_number', 'description', 'jarvies_status', 'ticket_priority', 'created_at']);

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
