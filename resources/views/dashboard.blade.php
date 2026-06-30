@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . (session('user.name') ?? session('user.company_name', 'User')) . '!')

@section('header-actions')
<a href="{{ route('tickets.create') }}" class="flex items-center space-x-2 bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>New Ticket</span>
</a>
@endsection

@section('content')

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-9 gap-4 mb-6">
    @php
        $statCards = [
            ['label' => 'Total',            'value' => $stats['total'],                   'color' => 'gray-900'],
            ['label' => 'Open',             'value' => $stats['open'],                    'color' => 'sky-600'],
            ['label' => 'In Process',       'value' => $stats['in_process'],              'color' => 'blue-600'],
            ['label' => 'Wait Customer',    'value' => $stats['waiting_on_customer'],     'color' => 'amber-600'],
            ['label' => 'Wait 3rd Party',   'value' => $stats['waiting_on_3rd_party'],   'color' => 'indigo-600'],
            ['label' => 'Wait Confirm',     'value' => $stats['waiting_to_confirmation'], 'color' => 'teal-600'],
            ['label' => 'Hold',             'value' => $stats['hold'],                    'color' => 'orange-600'],
            ['label' => 'Closed',           'value' => $stats['closed'],                  'color' => 'green-600'],
            ['label' => 'Pending Approval', 'value' => $stats['pending_approval'],        'color' => 'orange-500'],
        ];
    @endphp

    @foreach($statCards as $card)
    <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide leading-tight">{{ $card['label'] }}</p>
        <p class="text-2xl font-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- Chart + Recent Tickets --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Trend Chart --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Ticket Submissions</h2>
                <p class="text-xs text-gray-500">Last 30 days</p>
            </div>
            <span class="text-xs text-gray-400">{{ now()->format('d M Y') }}</span>
        </div>
        <div class="h-52">
            <canvas id="trendsChart"></canvas>
        </div>
    </div>

    {{-- Recent Tickets --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Recent Tickets</h3>
            <a href="{{ route('tickets.index') }}" class="text-xs text-red-600 hover:text-red-700 font-medium">View all →</a>
        </div>

        <div class="space-y-3">
            @forelse($recentTickets as $ticket)
            @php
                $statusColor = match($ticket->status) {
                    'closed'                  => 'bg-green-100 text-green-700',
                    'cancelled'               => 'bg-red-100 text-red-700',
                    'waiting_on_customer'     => 'bg-amber-100 text-amber-700',
                    'waiting_to_confirmation' => 'bg-teal-100 text-teal-700',
                    'waiting_on_3rd_party'    => 'bg-indigo-100 text-indigo-700',
                    'inprocess'               => 'bg-blue-100 text-blue-700',
                    'hold'                    => 'bg-orange-100 text-orange-700',
                    'open'                    => 'bg-sky-100 text-sky-700',
                    default                   => 'bg-gray-100 text-gray-600',
                };
            @endphp
            <a href="{{ route('tickets.show', $ticket->ticket_id) }}"
               class="block p-3 rounded-lg border border-gray-100 hover:border-red-200 hover:bg-red-50 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $ticket->description }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0 {{ $statusColor }}">
                        {{ ucfirst($ticket->status ?? '-') }}
                    </span>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <span class="text-xs text-gray-400 font-mono">{{ $ticket->ticket_number ?? '#' . $ticket->ticket_id }}</span>
                    <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
            </a>
            @empty
            <div class="text-center py-8">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-gray-400">No tickets yet</p>
                <a href="{{ route('tickets.create') }}" class="text-xs text-red-600 hover:text-red-700 font-medium mt-1 inline-block">Submit your first ticket →</a>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="{{ route('tickets.create') }}"
       class="flex items-center gap-4 bg-white rounded-xl border border-gray-100 p-5 hover:shadow-md hover:border-red-200 transition-all group">
        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0 group-hover:bg-red-800 transition-colors">
            <svg class="w-5 h-5 text-red-700 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-800">New Ticket</p>
            <p class="text-xs text-gray-400">Submit a support request</p>
        </div>
    </a>

    <a href="{{ route('tickets.index') }}"
       class="flex items-center gap-4 bg-white rounded-xl border border-gray-100 p-5 hover:shadow-md hover:border-blue-200 transition-all group">
        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-600 transition-colors">
            <svg class="w-5 h-5 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-800">My Tickets</p>
            <p class="text-xs text-gray-400">{{ $stats['open'] }} open · {{ $stats['closed'] }} closed</p>
        </div>
    </a>

    <a href="{{ route('profile') }}"
       class="flex items-center gap-4 bg-white rounded-xl border border-gray-100 p-5 hover:shadow-md hover:border-gray-300 transition-all group">
        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 group-hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5 text-gray-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-800">My Profile</p>
            <p class="text-xs text-gray-400">{{ session('user.company_name', 'View profile') }}</p>
        </div>
    </a>
</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('trendsChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 200);
gradient.addColorStop(0, 'rgba(220, 38, 38, 0.15)');
gradient.addColorStop(1, 'rgba(220, 38, 38, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($trendLabels),
        datasets: [{
            label: 'Tickets submitted',
            data: @json($trendData),
            borderColor: '#dc2626',
            backgroundColor: gradient,
            tension: 0.4,
            fill: true,
            pointRadius: 2,
            pointHoverRadius: 5,
            pointBackgroundColor: '#dc2626',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1f2937',
                padding: 10,
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' ticket' + (ctx.parsed.y !== 1 ? 's' : '')
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#9ca3af', font: { size: 10 }, maxTicksLimit: 10 }
            },
            y: {
                grid: { color: '#f3f4f6' },
                ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 1, precision: 0 },
                beginAtZero: true,
                min: 0,
            }
        },
        interaction: { intersect: false, mode: 'index' }
    }
});
</script>
@endpush