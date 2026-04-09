@extends('layouts.app')

@section('title', 'Submission History')
@section('page-title', 'Submission History')
@section('page-subtitle', 'All your submitted tickets and their validation status')

@section('header-actions')
<a href="{{ route('tickets.index') }}" class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    <span>Back to Tickets</span>
</a>
@endsection

@section('content')

@if($stagings->isEmpty())
<div class="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-50 rounded-full mb-4">
        <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <p class="text-gray-700 text-lg font-semibold mb-1">No pending tickets</p>
    <p class="text-gray-400 text-sm">All your submitted tickets have been processed.</p>
    <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 mt-6 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-xl hover:bg-red-900 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Ticket
    </a>
</div>
@else
<div class="space-y-3">
    @foreach($stagings as $s)
    @php
        $statusConfig = [
            'unvalidated' => ['bg' => 'bg-yellow-50 border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-700', 'label' => 'Pending Validation'],
            'approved'    => ['bg' => 'bg-green-50 border-green-200',  'badge' => 'bg-green-100 text-green-700',  'label' => 'Approved'],
            'rejected'    => ['bg' => 'bg-red-50 border-red-200',      'badge' => 'bg-red-100 text-red-700',      'label' => 'Rejected'],
        ];
        $cfg = $statusConfig[$s->status] ?? $statusConfig['unvalidated'];
    @endphp
    @php
        $href = match($s->status) {
            'approved' => $s->ticket_id ? url('/tickets/' . $s->ticket_id) : null,
            'rejected' => null,
            default    => url('/tickets/staging/' . $s->id),
        };
    @endphp
    <a @if($href) href="{{ $href }}" @else class="cursor-default" @endif
       class="flex items-start gap-4 px-5 py-4 rounded-xl border {{ $cfg['bg'] }} {{ $href ? 'hover:shadow-md transition-shadow' : '' }} block">

        {{-- Icon --}}
        <div class="mt-0.5 shrink-0">
            @if($s->status === 'approved')
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @elseif($s->status === 'rejected')
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="font-semibold text-gray-900 text-sm">{{ $s->description }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                @if($s->status === 'unvalidated')
                    <span class="text-xs text-gray-400 italic">Click to view detail →</span>
                @elseif($s->status === 'approved' && $s->ticket_id)
                    <span class="text-xs font-semibold text-red-700">View Ticket #{{ $s->ticket?->ticket_number ?? $s->ticket_id }} →</span>
                @endif
            </div>
            @if($s->status === 'rejected' && $s->rejection_reason)
                <p class="text-xs text-red-600 mb-1"><span class="font-semibold">Reason:</span> {{ $s->rejection_reason }}</p>
            @endif
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span>Submitted: {{ $s->created_at ? $s->created_at->format('d M Y, H:i') : '-' }}</span>
                @php
                    $priorityColors = ['Low' => 'text-green-600', 'Medium' => 'text-blue-600', 'High' => 'text-red-600'];
                @endphp
                <span class="text-gray-300">|</span>
                <span>Priority: <span class="font-semibold {{ $priorityColors[$s->ticket_priority] ?? 'text-gray-500' }}">{{ $s->ticket_priority ?? '—' }}</span></span>
            </div>
        </div>

        {{-- Arrow indicator for clickable items --}}
        @if($href)
        <div class="shrink-0 self-center text-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
        @endif
    </a>
    @endforeach
</div>
@endif

@endsection
