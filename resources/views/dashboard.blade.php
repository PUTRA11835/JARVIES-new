@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . session('user.name', 'User') . '!')

@section('header-actions')
<a href="{{ route('tickets.index') }}" class="flex items-center space-x-2 bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
    </svg>
    <span>View Tickets</span>
</a>
@endsection

@section('content')

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
        $statCards = [
            ['label' => 'Unresolved', 'value' => $stats['unresolved'] ?? 0, 'color' => 'gray-900', 'sublabel' => 'from last week'],
            ['label' => 'Overdue', 'value' => $stats['overdue'] ?? 0, 'color' => 'red-600', 'sublabel' => 'needs attention'],
            ['label' => 'Due Today', 'value' => $stats['due_today'] ?? 0, 'color' => 'amber-600', 'sublabel' => 'tickets pending'],
            ['label' => 'Open', 'value' => $stats['open'] ?? 0, 'color' => 'blue-600', 'sublabel' => 'in progress'],
            ['label' => 'On Hold', 'value' => $stats['on_hold'] ?? 0, 'color' => 'gray-600', 'sublabel' => 'waiting'],
            ['label' => 'Unassigned', 'value' => $stats['unassigned'] ?? 0, 'color' => 'purple-600', 'sublabel' => 'need assignment'],
        ];
    @endphp

    @foreach($statCards as $card)
    <div class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</p>
        <p class="text-2xl font-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $card['sublabel'] }}</p>
    </div>
    @endforeach
</div>

{{-- Chart Section --}}
<div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Today's Trends</h2>
            <p class="text-sm text-gray-500">{{ now()->format('jS M Y, h:i A') }}</p>
        </div>
        <div class="flex items-center space-x-6 flex-wrap gap-4">
            @php
                $trendMetrics = [
                    ['label' => 'Resolved', 'value' => $trendStats['resolved'] ?? 0, 'color' => 'gray-900'],
                    ['label' => 'Received', 'value' => $trendStats['received'] ?? 0, 'color' => 'gray-900'],
                    ['label' => 'Avg Response', 'value' => $trendStats['avg_response'] ?? '0m', 'color' => 'gray-900'],
                    ['label' => 'Resolution SLA', 'value' => ($trendStats['sla_percentage'] ?? 0) . '%', 'color' => 'green-600'],
                ];
            @endphp

            @foreach($trendMetrics as $metric)
            <div class="text-right">
                <p class="text-xs text-gray-500">{{ $metric['label'] }}</p>
                <p class="text-xl font-bold text-{{ $metric['color'] }}">{{ $metric['value'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
    
    <!-- Chart Canvas -->
    <div class="h-64">
        <canvas id="trendsChart"></canvas>
    </div>
    
    <!-- Legend -->
    <div class="flex items-center justify-center space-x-6 mt-4">
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
            <span class="text-sm text-gray-600">Today</span>
        </div>
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
            <span class="text-sm text-gray-600">Yesterday</span>
        </div>
    </div>
</div>

{{-- Bottom Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Unresolved Tickets --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">Unresolved Tickets</h3>
                <p class="text-xs text-gray-500">Across helpdesk</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="text-sm text-red-600 hover:text-red-700 font-medium">View details</a>
        </div>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                <span class="text-sm text-gray-600">Group</span>
                <span class="text-sm font-medium text-gray-500">Open</span>
            </div>
            @forelse($unresolvedTickets as $group)
            <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-700">{{ $group['group'] }}</span>
                <span class="text-sm font-semibold text-gray-900">{{ $group['count'] }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-500 text-center py-4">No unresolved tickets</p>
            @endforelse
        </div>
    </div>

    {{-- Customer Satisfaction --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <div class="mb-4">
            <h3 class="font-semibold text-gray-900">Customer Satisfaction</h3>
            <p class="text-xs text-gray-500">Across helpdesk this month</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 mb-1">Responses received</p>
                <p class="text-3xl font-bold text-gray-900">{{ $satisfaction['total_responses'] ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Positive</p>
                <div class="flex items-center space-x-2">
                    <p class="text-3xl font-bold text-green-600">{{ $satisfaction['positive'] ?? 0 }}%</p>
                    <span class="text-2xl">😊</span>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Neutral</p>
                <div class="flex items-center space-x-2">
                    <p class="text-2xl font-bold text-amber-500">{{ $satisfaction['neutral'] ?? 0 }}%</p>
                    <span class="text-xl">😐</span>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-1">Negative</p>
                <div class="flex items-center space-x-2">
                    <p class="text-2xl font-bold text-red-500">{{ $satisfaction['negative'] ?? 0 }}%</p>
                    <span class="text-xl">😞</span>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-4">
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                <div class="bg-green-500 h-full" style="width: {{ $satisfaction['positive'] ?? 0 }}%"></div>
                <div class="bg-amber-500 h-full" style="width: {{ $satisfaction['neutral'] ?? 0 }}%"></div>
                <div class="bg-red-500 h-full" style="width: {{ $satisfaction['negative'] ?? 0 }}%"></div>
            </div>
        </div>
    </div>

    {{-- To-Do List --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">To-do <span class="text-gray-400 font-normal">({{ count($todos) }})</span></h3>
        </div>
        
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($todos as $todo)
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="flex items-start space-x-3">
                    <input type="checkbox" class="mt-1 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500 cursor-pointer">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $todo['title'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $todo['description'] }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $todo['priority'] === 'high' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }} mt-2">
                            {{ $todo['due'] }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500 text-center py-8">No pending tasks</p>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
const ctx = document.getElementById('trendsChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 250);
gradient.addColorStop(0, 'rgba(220, 38, 38, 0.2)');
gradient.addColorStop(1, 'rgba(220, 38, 38, 0)');

const gradientGray = ctx.createLinearGradient(0, 0, 0, 250);
gradientGray.addColorStop(0, 'rgba(156, 163, 175, 0.1)');
gradientGray.addColorStop(1, 'rgba(156, 163, 175, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: Array.from({length: 31}, (_, i) => String(i + 1)),
        datasets: [
            {
                label: 'Today',
                data: [10, 15, 12, 8, 18, 15, 22, 28, 25, 32, 31, 32, 38, 22, 25, 38, 30, 22, 21, 28, 30, 32, 35, 38, 25, 28, 22, 45, 18, 30, 32],
                borderColor: '#dc2626',
                backgroundColor: gradient,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#dc2626',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                borderWidth: 2
            },
            {
                label: 'Yesterday',
                data: [5, 8, 6, 4, 10, 8, 15, 20, 25, 30, 35, 28, 22, 45, 48, 30, 42, 25, 28, 20, 22, 25, 28, 30, 12, 15, 18, 20, 22, 25, 28],
                borderColor: '#9ca3af',
                backgroundColor: gradientGray,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#9ca3af',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                borderWidth: 2,
                borderDash: [5, 5]
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1f2937',
                padding: 12,
                displayColors: true
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#9ca3af', font: { size: 11 } }
            },
            y: {
                grid: { color: '#f3f4f6' },
                ticks: { color: '#9ca3af', font: { size: 11 } },
                beginAtZero: true
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
</script>
@endpush