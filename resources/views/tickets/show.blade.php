@extends('layouts.app')

@section('sidebar-nav')
    {{-- Header: Back + Search --}}
    <div class="px-4 pt-4 pb-3 border-b border-red-700/50 shrink-0">
        <a href="{{ route('tickets.index') }}"
           class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-white/80 hover:bg-white/10 transition-all text-sm font-medium w-full">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span class="nav-text">Back to Tickets</span>
        </a>
        <div class="mt-2">
            <input type="text" id="sidebarSearch" placeholder="Search tickets…"
                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-sm text-white placeholder-white/50 focus:outline-none focus:bg-white/15 transition-all"
                   oninput="filterSidebarTickets()">
        </div>
    </div>

    {{-- Ticket List --}}
    <div id="sidebarTicketList" class="flex-1 overflow-y-auto px-2 py-2 space-y-1.5">
        <div id="sidebarLoading" class="flex items-center justify-center py-8">
            <p class="text-white/50 text-xs">Loading…</p>
        </div>
    </div>
@endsection

@section('title', ($ticket->ticket_number ?? 'Pending') . ' — Ticket')
@section('page-title', 'Support Ticket')
@section('page-subtitle', ($ticket->ticket_number ?? 'Pending') . ' — ' . Str::limit($ticket->description ?? '', 50))

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
/* Quill editor overrides */
.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 4px 8px !important;
    background: #f9fafb;
}
.ql-container.ql-snow { border: none !important; font-size: 13px; }
.ql-editor { min-height: 80px; max-height: 180px; overflow-y: auto; padding: 8px 12px; color: #374151; line-height: 1.6; }
.ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; font-size: 13px; left: 12px; }
.ql-toolbar .ql-formats { margin-right: 8px; }

/* Toolbar tooltip on hover */
.ql-toolbar button[title] { position: relative; }
.ql-toolbar button[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: calc(100% + 5px);
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: #fff;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 5px;
    white-space: nowrap;
    z-index: 9999;
    pointer-events: none;
}

/* Sidebar ticket items — white card style */
.sidebar-ticket-item {
    display: block;
    padding: 8px 10px 8px 12px;
    border-radius: 7px;
    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
    text-decoration: none;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,255,255,0.5);
    border-left: 3px solid transparent;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.sidebar-ticket-item:hover {
    background: rgba(255,255,255,1);
    border-left-color: #b91c1c;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.sidebar-ticket-item.active {
    background: rgba(255,255,255,1);
    border-left-color: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Message bubbles */
.message-bubble { max-width: 85%; word-break: break-word; }

/* Customer (self) — right side, blue-50 bg, sharp bottom-right corner */
.bubble-customer {
    background: #eff6ff;
    border-radius: 12px 12px 4px 12px;
}

/* Employee / agent — left side, gray-100 bg, sharp bottom-left corner */
.bubble-employee {
    background: #f3f4f6;
    border-radius: 12px 12px 12px 4px;
}

/* Channel badge */
.msg-channel-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 4px; vertical-align: middle; }
.msg-channel-email { background: #dbeafe; color: #1d4ed8; }
.msg-channel-web   { background: #f0fdf4; color: #15803d; }

/* Status delivery indicator (WhatsApp-style) — hanya untuk pesan customer */
.msg-status-row {
    display: flex; justify-content: flex-end;
    margin-top: 6px; padding-top: 4px;
    border-top: 1px solid rgba(0,0,0,0.04);
}
.msg-status {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; color: #9ca3af; user-select: none; cursor: default;
    line-height: 1;
}
.msg-status.read { color: #2563eb; font-weight: 600; }
.msg-status .check-pair {
    display: inline-flex; align-items: center; flex-shrink: 0;
}
.msg-status .check-pair svg { width: 12px; height: 12px; }
.msg-status .check-pair svg + svg { margin-left: -7px; }

/* Email HTML body — scoped so styles don't leak outside bubble */
.email-html-body               { word-break: break-word; }
.email-html-body p             { margin-bottom: 0.3rem; }
.email-html-body a             { color: #2563eb; text-decoration: underline; }
.email-html-body ul,
.email-html-body ol            { padding-left: 1.25rem; margin-bottom: 0.4rem; }
.email-html-body blockquote    { border-left: 3px solid #d1d5db; padding-left: 0.75rem; color: #6b7280; margin: 0.25rem 0; }
.email-html-body img           { max-width: 100%; height: auto; border-radius: 6px; }
.email-html-body table         { border-collapse: collapse; font-size: 12px; max-width: 100%; }
.email-html-body td,
.email-html-body th            { border: 1px solid #e5e7eb; padding: 4px 8px; }

/* CC tag pills */
.cc-tag { display:inline-flex; align-items:center; gap:3px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:11px; border-radius:9999px; padding:1px 8px; max-width:200px; }
.cc-tag span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cc-tag button { color:#93c5fd; background:none; border:none; cursor:pointer; line-height:1; padding:0; margin-left:2px; font-size:13px; }
.cc-tag button:hover { color:#ef4444; }
#ccRow { border-bottom: 1px solid #e5e7eb; }

/* Meeting card — start (purple) */
.meeting-card-start { background:#faf5ff; border:1px solid #d8b4fe; border-radius:12px; padding:14px 16px; max-width:380px; text-align:left; }
.meeting-card-start .mc-header strong { color:#6b21a8; }
.meeting-card-start .mc-divider { border-top-color:#d8b4fe; }
.meeting-card-start .mc-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:#6b21a8; background:#ede9fe; border:1px solid #c4b5fd; border-radius:9999px; padding:2px 8px; }

/* Meeting card — end (green) */
.meeting-card-end { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px 16px; max-width:380px; text-align:left; }
.meeting-card-end .mc-header strong { color:#166534; }
.meeting-card-end .mc-divider { border-top-color:#bbf7d0; }
.meeting-card-end .mc-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:#166534; background:#dcfce7; border:1px solid #bbf7d0; border-radius:9999px; padding:2px 8px; }

/* Shared meeting card internals */
.mc-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.mc-header strong { font-size:14px; font-weight:700; }
.mc-row { display:flex; align-items:flex-start; gap:6px; margin-bottom:6px; font-size:12px; color:#374151; }
.mc-row span:first-child { flex-shrink:0; }
.mc-divider { border:none; border-top:1px solid; margin:10px 0; }
.mc-join-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:9px; font-size:12px; font-weight:700; text-decoration:none; margin-top:4px; transition:all .15s; cursor:pointer; border:none; }
.mc-join-btn:hover { transform:translateY(-1px); filter:brightness(1.08); box-shadow:0 4px 12px rgba(0,0,0,.18); }
.mc-join-btn.zoom   { background:#2D8CFF; color:#fff; box-shadow:0 2px 8px rgba(45,140,255,.35); }
.mc-join-btn.gmeet  { background:#1a73e8; color:#fff; box-shadow:0 2px 8px rgba(26,115,232,.35); }
.mc-join-btn.teams  { background:#464EB8; color:#fff; box-shadow:0 2px 8px rgba(70,78,184,.35); }
.mc-join-btn.webex  { background:#00B4D8; color:#fff; box-shadow:0 2px 8px rgba(0,180,216,.35); }
.mc-join-btn.generic { background:linear-gradient(135deg,#6d28d9,#7c3aed); color:#fff; box-shadow:0 2px 8px rgba(109,40,217,.35); }

/* Legacy meeting card (backward compat) */
.meeting-card { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px 16px; max-width:380px; text-align:left; }
.meeting-card-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.meeting-card-header strong { font-size:14px; color:#166534; font-weight:700; }
.meeting-card-row { display:flex; align-items:flex-start; gap:6px; margin-bottom:6px; font-size:12px; color:#374151; }
.meeting-card-row span:first-child { flex-shrink:0; }
.meeting-card-link { color:#2563eb; text-decoration:underline; word-break:break-all; }
.meeting-card-divider { border:none; border-top:1px solid #bbf7d0; margin:10px 0; }
.meeting-status-ongoing { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:#b45309; background:#fef3c7; border:1px solid #fde68a; border-radius:9999px; padding:2px 8px; }
.meeting-status-ended   { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:#166534; background:#dcfce7; border:1px solid #bbf7d0; border-radius:9999px; padding:2px 8px; }

/* Meeting modal */
#meetingModal .modal-field { width:100%; border:1px solid #d1d5db; border-radius:10px; padding:8px 12px; font-size:13px; color:#374151; resize:none; outline:none; transition:border-color .15s; }
#meetingModal .modal-field:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); }

/* Quill message content */
.message-content p             { margin-bottom: 0.25rem; }
.message-content p:last-child  { margin-bottom: 0; }
.message-content ul,
.message-content ol            { padding-left: 1.5rem; margin-bottom: 0.5rem; }
.message-content blockquote    { border-left: 3px solid #d1d5db; padding-left: 0.75rem; color: #6b7280; }
.message-content a             { color: #2563eb; text-decoration: underline; }
.message-content img           { max-width: 100%; height: auto; border-radius: 4px; }
.message-content h1, .message-content h2, .message-content h3 { font-weight: 600; margin-bottom: 0.25rem; }

/* Sidebar ticket badges */
.sb-badge { display:inline-flex; align-items:center; font-size:10px; font-weight:600; padding:1px 6px; border-radius:4px; line-height:1.4; }
.sb-prio-very-high { background:#ede9fe; color:#6d28d9; }
.sb-prio-high      { background:#fee2e2; color:#b91c1c; }
.sb-prio-medium    { background:#dbeafe; color:#1d4ed8; }
.sb-prio-low       { background:#dcfce7; color:#15803d; }
/* Status badge classes */
.sb-jstat-open           { background:#e0f2fe; color:#0369a1; }
.sb-jstat-in-process     { background:#ede9fe; color:#6d28d9; }
.sb-jstat-wait-customer  { background:#fef9c3; color:#a16207; }
.sb-jstat-wait-3rdparty  { background:#e0e7ff; color:#3730a3; }
.sb-jstat-wait-confirm   { background:#ccfbf1; color:#0f766e; }
.sb-jstat-hold           { background:#ffedd5; color:#c2410c; }
.sb-jstat-cancelled      { background:#fee2e2; color:#b91c1c; }
.sb-jstat-closed         { background:#dcfce7; color:#15803d; }
.sb-jstat-default        { background:#f3f4f6; color:#4b5563; }
</style>
@endpush

@section('content')
@php
    $statusColors = [
        'open'        => 'bg-blue-100 text-blue-700',
        'in_progress' => 'bg-yellow-100 text-yellow-700',
        'hold'        => 'bg-orange-100 text-orange-700',
        'cancel'      => 'bg-gray-100 text-gray-500',
        'closed'      => 'bg-green-100 text-green-700',
        'reply'       => 'bg-purple-100 text-purple-700',
    ];
    $typeColors = [
        'Incident'       => 'bg-red-100 text-red-700',
        'Service Request'=> 'bg-indigo-100 text-indigo-700',
        'Change Request' => 'bg-amber-100 text-amber-700',
        'Consult'        => 'bg-teal-100 text-teal-700',
    ];
    $priorityColors = [
        'Low'    => 'bg-green-50 text-green-700 border-green-200',
        'Medium' => 'bg-blue-50 text-blue-700 border-blue-200',
        'High'   => 'bg-red-50 text-red-700 border-red-200',
    ];
    $meetingAllowedRoles = ['Administrator', 'Delivery Support Head', 'Helpdesk', 'RPMO Head'];
    $sessionUser         = session('user');
    $canUseMeeting       = $sessionUser && (
        ($sessionUser['role']['id'] ?? 0) === 1 ||
        (($sessionUser['role']['id'] ?? 0) === 2 &&
            collect($meetingAllowedRoles)->contains(
                fn($r) => strcasecmp($r, $sessionUser['role']['name'] ?? '') === 0
            )
        )
    );

    $agentName = 'Unassigned';
    if ($ticket->employee) {
        $fn = $ticket->employee->basicData->first_name ?? '';
        $ln = $ticket->employee->basicData->last_name ?? '';
        $agentName = trim($fn . ' ' . $ln) ?: 'Assigned';
    }
    $customerName = $ticket->customer?->basicData?->name_1 ?? session('user.company_name') ?? 'Customer';
    $approvedMandays = \App\Models\CustomerMandays::where('ticket_id', $ticket->ticket_id)
        ->where('status', 'approved')
        ->orderByDesc('version')
        ->first();
@endphp

<div class="flex gap-6" style="height: calc(100vh - 140px); min-height: 500px;">

    {{-- ═══ MAIN: Conversation Thread ═══ --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-w-0">

        {{-- Ticket Header --}}
        <div class="px-6 py-4 border-b border-gray-200 shrink-0 flex items-start gap-2">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <h2 class="text-base font-bold text-gray-900">{{ $ticket->description ?: 'No description' }}</h2>
                    <span class="text-sm text-gray-400 font-mono">{{ $ticket->ticket_number ?? 'Pending' }}</span>
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                    @if($ticket->ticket_type)
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $typeColors[$ticket->ticket_type] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $ticket->ticket_type }}
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                    <span class="font-medium text-gray-700">{{ $customerName }}</span>
                    <span class="text-gray-300">|</span>
                    <span>{{ $ticket->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>
            <button id="toggleRightPanelBtn" onclick="toggleTicketRightPanel()" title="Toggle properties panel"
                class="flex-shrink-0 w-9 h-9 items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all hidden xl:flex">
                <svg id="rightPanelIconCollapse" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <svg id="rightPanelIconExpand" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        {{-- Messages Thread --}}
        <div id="messagesThread" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
            <div id="messagesLoading" class="flex items-center justify-center py-10">
                <svg class="animate-spin h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-gray-400 text-sm">Loading messages...</span>
            </div>
        </div>

        {{-- Compose Area --}}
        <div class="border-t border-gray-200 shrink-0">

            {{-- Toggle strip: channel indicator + collapse button --}}
            <div class="flex items-center pr-3">
                <div id="channelIndicator" class="flex-1">
                @if($ticket->email_thread_id || $ticket->channel === 'email')
                <div class="px-4 pt-2 pb-0.5 flex items-center gap-1.5 text-xs text-blue-700">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    @if($ticket->channel === 'email')
                        <span>Replies will be sent to the support team via <strong>Email</strong></span>
                    @else
                        <span>Email thread active — helpdesk replies will be sent to <strong>your email</strong></span>
                    @endif
                </div>
                @else
                <div class="px-4 pt-2 pb-0.5 flex items-center gap-1.5 text-xs text-gray-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Replies only visible in <strong>Jarvies</strong> — no email will be sent</span>
                </div>
                @endif
                </div>
                <button onclick="toggleReplyBox()" title="Toggle reply box"
                    class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all">
                    <svg id="replyToggleIconDown" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <svg id="replyToggleIconUp" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Collapsible compose content --}}
            <div id="replyComposeInner" style="max-height:600px;overflow:hidden;opacity:1;transition:max-height .2s ease,opacity .2s ease;">

                {{-- To row (email channel only, read-only) --}}
                @if(($ticket->email_thread_id || $ticket->channel === 'email') && session('user.email'))
                <div class="px-4 pt-1.5">
                    <div class="flex items-center gap-2 text-xs text-gray-500 px-2 py-1 border-b border-gray-100">
                        <span class="font-semibold text-gray-500 w-5 shrink-0">To</span>
                        <span class="text-gray-700">{{ session('user.email') }}</span>
                    </div>
                </div>
                @endif

                {{-- CC row (email channel only) --}}
                @if($ticket->email_thread_id || $ticket->channel === 'email')
                <div class="px-4 pt-1" id="ccRow">
                    <div class="flex flex-wrap items-center gap-1 min-h-[30px] px-2 py-1 cursor-text"
                         onclick="document.getElementById('ccInput').focus()">
                        <span class="text-[11px] font-semibold text-gray-500 w-5 shrink-0">CC</span>
                        <div id="ccTagsContainer" class="flex flex-wrap gap-1 items-center"></div>
                        <input type="text" id="ccInput"
                               placeholder="Add email, press Enter…"
                               class="text-xs border-none bg-transparent outline-none flex-1 min-w-40 placeholder-gray-300 py-0.5"
                               onkeydown="handleCcKeydown(event)"
                               onblur="commitCcInput()"
                               onpaste="handleCcPaste(event)">
                    </div>
                </div>
                @endif

                <div class="px-4 pt-2 pb-2">
                    <div class="bg-white border border-gray-300 rounded-lg overflow-hidden">
                        <div id="replyEditor" style="min-height: 100px; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    {{-- Attachment Preview --}}
                    <div id="attachmentPreview" style="display:none" class="mt-2 flex-wrap gap-2"></div>

                    {{-- Hidden file input --}}
                    <input type="file" id="attachmentInput" multiple class="hidden"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.csv">

                    <div class="flex items-center justify-end mt-2 mb-1 gap-2">
                        <span id="attachCount" class="hidden text-xs text-blue-600 font-medium mr-auto"></span>

                        @if($canUseMeeting)
                        {{-- Meeting button — toggled by JS based on active meeting state --}}
                        <button id="meetingBtn" onclick="openMeetingStartModal()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-100 text-violet-700 border border-violet-300 text-xs font-semibold rounded-lg hover:bg-violet-200 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span id="meetingBtnLabel">Meeting</span>
                        </button>
                        @endif

                        <button onclick="sendReply()" id="sendBtn"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-red-700 text-white text-xs font-semibold rounded-lg hover:bg-red-800 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg id="sendIcon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
                            </svg>
                            <svg id="sendSpinner" class="hidden animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Send Reply
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══ RIGHT: Properties ═══ --}}
    <div id="ticketRightPanel" class="hidden xl:flex xl:flex-col bg-white rounded-xl border border-gray-200 shadow-sm overflow-y-auto shrink-0" style="width:288px;transition:width .2s ease,opacity .2s ease;">
        <div class="p-5">
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide mb-4">Properties</h4>
            <div class="space-y-3">

                {{-- Ticket Number --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Ticket No.</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200 font-mono break-all">
                        {{ $ticket->ticket_number ?? 'Pending' }}
                    </p>
                </div>

                {{-- Status --}}
                @if(session('user.role.id') == 1 || session('user.role.id') == 2)
                {{-- Admin/Employee: radio buttons --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-2 block">Status</label>
                    <div class="flex flex-col gap-1" id="statusRadioGroup">
                        @php
                            $statusOptions = [
                                'open'                    => ['label' => 'Open',                  'color' => 'sky'],
                                'in process'              => ['label' => 'In Process',            'color' => 'blue'],
                                'waiting on customer'     => ['label' => 'Waiting on Customer',   'color' => 'amber'],
                                'waiting on 3rd party'    => ['label' => 'Waiting on 3rd Party',  'color' => 'indigo'],
                                'waiting to confirmation' => ['label' => 'Waiting Confirmation',  'color' => 'purple'],
                                'hold'                    => ['label' => 'Hold',                  'color' => 'orange'],
                                'cancelled'               => ['label' => 'Cancelled',             'color' => 'red'],
                                'closed'                  => ['label' => 'Closed',                'color' => 'green'],
                            ];
                            $currentStatus = $ticket->status ?? 'open';
                        @endphp
                        @foreach($statusOptions as $val => $opt)
                        <button type="button"
                                onclick="selectStatus('{{ $val }}')"
                                data-status="{{ $val }}"
                                class="status-radio-btn w-full text-left px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all
                                       {{ $val === $currentStatus
                                            ? 'bg-' . $opt['color'] . '-100 text-' . $opt['color'] . '-700 border-' . $opt['color'] . '-400 ring-1 ring-' . $opt['color'] . '-400'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full border-2 flex-shrink-0 status-dot
                                             {{ $val === $currentStatus ? 'bg-' . $opt['color'] . '-500 border-' . $opt['color'] . '-500' : 'border-gray-300' }}"></span>
                                {{ $opt['label'] }}
                            </span>
                        </button>
                        @endforeach
                    </div>
                    <button onclick="saveStatus()"
                            id="saveStatusBtn"
                            class="mt-2 w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-red-700 hover:bg-red-800 text-white text-xs font-semibold rounded-lg transition-all shadow-sm disabled:opacity-50">
                        <svg id="saveStatusIcon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg id="saveStatusSpinner" class="hidden animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Kirim
                    </button>
                </div>
                @else
                {{-- Customer: read-only --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Status</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200 capitalize">
                        {{ $ticket->status ?? 'open' }}
                    </p>
                </div>
                @endif

                {{-- Priority --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Priority</label>
                    <p class="text-xs px-2.5 py-1.5 rounded-lg border font-medium {{ $priorityColors[$ticket->ticket_priority] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                        {{ $ticket->ticket_priority ?? '—' }}
                    </p>
                </div>

                {{-- Ticket Type --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Ticket Type</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                        {{ $ticket->ticket_type ?? '—' }}
                    </p>
                </div>

                {{-- Man Days (Approved) --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Man Days</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                        {{ $approvedMandays ? number_format($approvedMandays->total_mandays, 2) . ' days' : '—' }}
                    </p>
                </div>

                {{-- Divider --}}
                <div class="pt-3 border-t border-gray-100 space-y-3">
                    {{-- Customer --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Customer</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $customerName }}
                        </p>
                    </div>

                    @if($ticket->end_customer_id)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">For End Customer</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-blue-50 rounded-lg border border-blue-200">
                            &#8627; {{ $ticket->endCustomer?->basicData?->name_1 ?? 'N/A' }}
                        </p>
                    </div>
                    @endif

                    {{-- Start Date --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Start Date</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->start_date ? \Carbon\Carbon::parse($ticket->start_date)->format('d M Y') : '—' }}
                        </p>
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Due Date</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->end_date ? \Carbon\Carbon::parse($ticket->end_date)->format('d M Y') : '—' }}
                        </p>
                    </div>

                    {{-- Created --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Created</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                        </p>
                    </div>

                    {{-- Created By --}}
                    @if($ticket->submitted_by_name || $ticket->submitted_by_email)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Created By</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->submitted_by_name ?? $ticket->submitted_by_email }}
                        </p>
                    </div>
                    @endif
                </div>

                {{-- Customer Actions --}}
                @if(!in_array($ticket->status, ['closed', 'cancelled']))
                <div class="pt-4 border-t border-gray-100 space-y-2">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Actions</p>

                    {{-- Close Ticket --}}
                    <button onclick="customerCloseTicket()"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold border transition-all
                                   bg-green-50 text-green-700 border-green-200 hover:bg-green-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Close Ticket
                    </button>

                    {{-- Cancel Ticket --}}
                    <button onclick="customerCancelTicket()"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold border transition-all
                                   bg-red-50 text-red-600 border-red-200 hover:bg-red-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel Ticket
                    </button>
                </div>
                @endif

            </div>
        </div>
    </div>

</div>


{{-- ==================== CONFIRMATION MODAL ==================== --}}
<div id="confirmModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden" aria-modal="true" role="dialog">
    <div id="confirmModalBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
    <div id="confirmModalCard"
         class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-200 scale-95 opacity-0">

        {{-- Top Icon --}}
        <div class="flex flex-col items-center pt-8 pb-2 px-6">
            <div id="confirmModalIconWrap" class="w-16 h-16 rounded-full flex items-center justify-center mb-4"></div>
            <h3 id="confirmModalTitle" class="text-base font-bold text-gray-900 text-center mb-1"></h3>
            <p id="confirmModalMessage" class="text-sm text-gray-500 text-center leading-relaxed"></p>
        </div>

        {{-- Info Callout --}}
        <div id="confirmModalInfo" class="hidden mx-6 mt-3 mb-1 p-3 rounded-xl text-xs leading-relaxed"></div>

        {{-- Buttons --}}
        <div class="flex gap-3 p-6">
            <button onclick="closeConfirmModal()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 active:bg-gray-100 transition-all">
                Keep Ticket
            </button>
            <button id="confirmModalBtn"
                    class="flex-1 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all">
                Confirm
            </button>
        </div>
    </div>
</div>

@if($canUseMeeting)
{{-- ==================== MEETING MODAL ==================== --}}
<div id="meetingModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMeetingModal()"></div>
    <div id="meetingModalCard"
         class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-200 scale-95 opacity-0 overflow-hidden">

        {{-- Start Meeting Panel --}}
        <div id="meetingPanelStart">
            <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Start Meeting?</h3>
                    <p class="text-xs text-gray-500 mt-0.5">SLA clock will be paused while meeting is in progress.</p>
                </div>
            </div>
            <div class="px-6 py-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Topic <span class="font-normal text-gray-400">(optional)</span></label>
                <textarea id="meetingTopicInput" rows="2"
                    class="modal-field"
                    placeholder="What will be discussed in this meeting?"></textarea>
                <label class="block text-xs font-semibold text-gray-600 mt-3 mb-1.5">
                    <svg class="inline w-3.5 h-3.5 mr-1 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Meeting Link <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <input id="meetingLinkInput" type="url"
                    class="modal-field"
                    placeholder="https://zoom.us/j/... or Google Meet / Teams link">
                <div class="mt-3 flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span>Meeting time is recorded as <strong>waiting hours</strong> and does not count toward SLA resolution time.</span>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button onclick="closeMeetingModal()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button onclick="doStartMeeting()" id="startMeetingConfirmBtn"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition-all flex items-center justify-center gap-1.5">
                    <svg id="startMeetingIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                    </svg>
                    <svg id="startMeetingSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Start Meeting
                </button>
            </div>
        </div>

        {{-- End Meeting Panel --}}
        <div id="meetingPanelEnd" class="hidden">
            <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">End Meeting?</h3>
                    <p class="text-xs text-gray-500 mt-0.5">SLA clock will resume and meeting duration added to waiting hours.</p>
                </div>
            </div>
            <div class="px-6 py-4">
                <div id="meetingDurationLabel" class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 mb-3 hidden">
                    <svg class="inline w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="meetingDurationText"></span>
                </div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Summary <span class="font-normal text-gray-400">(optional)</span></label>
                <textarea id="meetingSummaryInput" rows="3"
                    class="modal-field"
                    placeholder="Brief summary of what was discussed..."></textarea>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button onclick="closeMeetingModal()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button onclick="doEndMeeting()" id="endMeetingConfirmBtn"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-all flex items-center justify-center gap-1.5">
                    <svg id="endMeetingIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                    <svg id="endMeetingSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    End Meeting
                </button>
            </div>
        </div>

    </div>
</div>
@endif

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const ticketId   = {{ $ticket->ticket_id }};
const ticketDescription  = @json($ticket->description ?? '');
const ticketCustomerName = @json(session('user.name') ?? session('user.company_name') ?? 'Customer');
const ticketCreatedAt    = @json($ticket->created_at->toIso8601String());
const isEmailChannel     = {{ ($ticket->email_thread_id || $ticket->channel === 'email') ? 'true' : 'false' }};

// CC state — dinormalisasi dari ticket.cc_emails (bisa string atau {address,name})
let ccEmails = @json(
    collect($ticket->cc_emails ?? [])
        ->map(fn($c) => is_array($c) ? ($c['address'] ?? '') : (string) $c)
        ->filter()
        ->values()
        ->all()
);

let CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

let lastMessageId = null;
let allSidebarTickets  = [];
let quill;
let selectedFiles      = [];

// ── Meeting state ──────────────────────────────────────────────────────────
const canUseMeeting = {{ $canUseMeeting ? 'true' : 'false' }};
let activeMeeting   = null; // { id, topic, started_by_name, started_at }

document.addEventListener('DOMContentLoaded', function () {
    // Init Quill rich text editor
    quill = new Quill('#replyEditor', {
        theme: 'snow',
        placeholder: 'Type your reply here...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote'],
                [{ 'list': 'bullet' }, { 'list': 'ordered' }],
                [{ 'header': [1, 2, 3, false] }],
                ['link'],
                ['clean']
            ],
            keyboard: {
                bindings: {
                    ctrlEnter: {
                        key: 13,
                        ctrlKey: true,
                        handler: () => sendReply()
                    }
                }
            }
        }
    });

    // Inject attachment button into Quill toolbar
    const toolbar = document.querySelector('.ql-toolbar');
    if (toolbar) {
        const tipMap = {
            'ql-bold': 'Bold', 'ql-italic': 'Italic', 'ql-underline': 'Underline',
            'ql-strike': 'Strikethrough', 'ql-blockquote': 'Blockquote',
            'ql-link': 'Link', 'ql-clean': 'Clear Formatting',
        };
        Object.entries(tipMap).forEach(([cls, label]) => {
            const btn = toolbar.querySelector('.' + cls);
            if (btn) btn.setAttribute('title', label);
        });
        toolbar.querySelectorAll('.ql-list').forEach(btn => {
            btn.setAttribute('title', btn.value === 'ordered' ? 'Numbered List' : 'Bullet List');
        });
        const headerEl = toolbar.querySelector('.ql-header');
        if (headerEl) headerEl.setAttribute('title', 'Heading');

        const attachGroup = document.createElement('span');
        attachGroup.className = 'ql-formats';
        attachGroup.innerHTML = `
            <button type="button" title="Attach File"
                    onclick="document.getElementById('attachmentInput').click()"
                    style="width:auto;padding:2px 7px;display:inline-flex;align-items:center;gap:4px;border-radius:3px;">
                <svg style="width:12px;height:12px;color:#555" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <span style="font-size:11px;font-weight:500;color:#444;line-height:1.5">Attachment</span>
            </button>`;
        toolbar.appendChild(attachGroup);
    }

    // Attachment input listener
    document.getElementById('attachmentInput').addEventListener('change', function () {
        const maxSize = 10 * 1024 * 1024;
        Array.from(this.files).forEach(file => {
            if (file.size > maxSize) { showNotification(`${file.name} is too large (max 10 MB)`, 'error'); return; }
            if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) selectedFiles.push(file);
        });
        this.value = '';
        renderAttachmentPreview();
    });

    renderCcTags();
    loadMessages();
    loadSidebarTickets();
    if (canUseMeeting) loadActiveMeeting();
    startPolling();
});

// ==================== CC MANAGEMENT ====================
function renderCcTags() {
    const container = document.getElementById('ccTagsContainer');
    if (!container) return;
    container.innerHTML = ccEmails.map((email, i) =>
        `<span class="cc-tag">
            <span>${escHtml(email)}</span>
            <button type="button" onclick="removeCcTag(${i})" title="Remove">&times;</button>
        </span>`
    ).join('');
}

function removeCcTag(index) {
    ccEmails.splice(index, 1);
    renderCcTags();
}

function handleCcKeydown(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        commitCcInput();
    } else if (e.key === 'Backspace' && e.target.value === '' && ccEmails.length > 0) {
        ccEmails.pop();
        renderCcTags();
    }
}

function commitCcInput() {
    const input = document.getElementById('ccInput');
    if (!input) return;
    const parts = input.value.split(/[,;\s]+/).map(s => s.trim()).filter(Boolean);
    let added = false;
    for (const email of parts) {
        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && !ccEmails.includes(email)) {
            ccEmails.push(email);
            added = true;
        }
    }
    if (added) renderCcTags();
    input.value = '';
}

function handleCcPaste(e) {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text');
    const input = document.getElementById('ccInput');
    if (input) { input.value = text; commitCcInput(); }
}

// ==================== POLLING ====================
function startPolling() {
    setInterval(() => loadMessages(), 15000);
}

// ==================== ATTACHMENT HANDLING ====================
function renderAttachmentPreview() {
    const preview = document.getElementById('attachmentPreview');
    const countEl = document.getElementById('attachCount');
    if (selectedFiles.length === 0) {
        preview.style.display = 'none';
        countEl.classList.add('hidden');
        return;
    }
    preview.style.display = 'flex';
    countEl.classList.remove('hidden');
    countEl.textContent = selectedFiles.length + (selectedFiles.length === 1 ? ' file' : ' files');
    preview.innerHTML = selectedFiles.map((file, idx) => {
        const fileType = file.type.startsWith('image/')        ? 'image'
                       : file.type === 'application/pdf'       ? 'pdf'
                       : /\.(doc|docx)$/i.test(file.name)      ? 'document'
                       : /\.(xls|xlsx|csv)$/i.test(file.name)  ? 'spreadsheet'
                       : /\.(zip|rar)$/i.test(file.name)       ? 'archive' : 'generic';
        const icon = attachmentIcon(fileType, file.type, 'w-4 h-4');
        const size = formatFileSize(file.size);
        return `<div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 text-xs max-w-[180px]">
            <span class="flex-shrink-0">${icon}</span>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-700 truncate" title="${escHtml(file.name)}">${escHtml(file.name)}</p>
                ${size ? `<p class="text-[10px] text-gray-400">${size}</p>` : ''}
            </div>
            <button type="button" onclick="removeAttachment(${idx})"
                    class="flex-shrink-0 text-gray-400 hover:text-red-500 text-xs leading-none">✕</button>
        </div>`;
    }).join('');
}

function removeAttachment(idx) {
    selectedFiles.splice(idx, 1);
    renderAttachmentPreview();
}

function resetAttachments() {
    selectedFiles = [];
    const inp = document.getElementById('attachmentInput');
    if (inp) inp.value = '';
    renderAttachmentPreview();
}

// ==================== MESSAGES ====================
async function loadMessages() {
    const thread  = document.getElementById('messagesThread');
    const loading = document.getElementById('messagesLoading');

    try {
        const res  = await fetch(`/tickets/${ticketId}/messages`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin'
        });
        const data = await res.json();

        if (loading) loading.style.display = 'none';

        const messages = (data.success && data.data) ? data.data : [];

        if (lastMessageId === null) {
            // Initial load — render all messages
            thread.innerHTML = '';
            if (messages.length === 0) {
                thread.innerHTML = createFallbackMessage();
            } else {
                messages.forEach(m => thread.insertAdjacentHTML('beforeend', createMessageBubble(m)));
                lastMessageId = messages[messages.length - 1].id;
            }
            thread.scrollTop = thread.scrollHeight;
        } else {
            // Polling — only append messages newer than lastMessageId
            const newMessages = messages.filter(m => m.id > lastMessageId);
            if (newMessages.length > 0) {
                newMessages.forEach(m => thread.insertAdjacentHTML('beforeend', createMessageBubble(m)));
                lastMessageId = newMessages[newMessages.length - 1].id;
                thread.scrollTop = thread.scrollHeight;

                // Auto-populate CC input saat ada reply helpdesk/employee yang bawa CC —
                // customer tidak perlu mengetik ulang. Customer tetap bisa hapus tag.
                mergeAgentCcFromMessages(newMessages);
            }
        }

    } catch (err) {
        if (loading) loading.style.display = 'none';
        if (lastMessageId === null) {
            thread.innerHTML = `<div class="text-center py-8 text-red-400 text-sm">Failed to load messages.</div>`;
        }
    }
}

function createFallbackMessage() {
    if (!ticketDescription) {
        return `<div class="flex items-center justify-center py-10 text-gray-400 text-sm">No messages yet. Start the conversation by sending a reply.</div>`;
    }
    const initials = (ticketCustomerName || '?').substring(0, 1).toUpperCase();
    const timeStr  = formatFullDate(new Date(ticketCreatedAt));
    return `
        <div class="flex gap-3 flex-row-reverse">
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
            <div class="text-right">
                <div class="flex flex-col mb-1 items-end">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <span class="text-sm font-semibold text-gray-900">${escHtml(ticketCustomerName)}</span>
                        <span class="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-semibold">Initial</span>
                        <span class="text-xs text-gray-400">${timeStr}</span>
                    </div>
                </div>
                <div class="message-bubble bubble-customer p-3 inline-block text-left">
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${escHtml(ticketDescription)}</p>
                </div>
            </div>
        </div>`;
}

// ==================== STATUS INDICATOR ====================
const ICON_CHECK_SINGLE = `<span class="check-pair"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg></span>`;
const ICON_CHECK_DOUBLE = `<span class="check-pair"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg></span>`;

/**
 * Status indikator delivery untuk pesan customer → agent.
 * - Sent (✓ abu-abu)            : pesan tersimpan ke DB
 * - Sent via email (✓✓ abu-abu) : email dikirim ke helpdesk inbox
 * - Read (✓✓ biru)              : agent sudah baca pesan
 *
 * Hanya ditampilkan untuk pesan customer sendiri (kanan).
 */
function statusIndicator(msg) {
    if (msg.sender_type !== 'customer') return '';

    let readAtTip = '';
    if (msg.read_at) {
        try {
            const t = new Date(msg.read_at).toLocaleString('en-GB', {
                timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: false
            }) + ' (WIB)';
            readAtTip = `Read at ${t}`;
        } catch (e) { readAtTip = 'Read by agent'; }
    } else {
        readAtTip = 'Read by agent';
    }

    if (msg.is_read_by_agent) {
        return `<div class="msg-status read" title="${readAtTip}">${ICON_CHECK_DOUBLE}<span>Read</span></div>`;
    }

    if (msg.channel === 'email' && msg.email_message_id) {
        return `<div class="msg-status" title="Delivered to helpdesk email">${ICON_CHECK_DOUBLE}<span>Sent via email</span></div>`;
    }

    return `<div class="msg-status" title="Saved to ticket">${ICON_CHECK_SINGLE}<span>Sent</span></div>`;
}

// ==================== MEETING CARD ====================

function parseMeetingData(msg) {
    // Legacy format: JSON body with _type:'meeting'
    try {
        const raw = msg.message || msg.message_body || '';
        if (!raw) return null;
        const d = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (d && d._type === 'meeting') return d;
    } catch (_) {}
    return null;
}

function parseMeetingBody(msg) {
    // JSON body format: _type = 'meeting_start' | 'meeting_end' (stored in message field)
    try {
        const raw = msg.message || '';
        if (!raw) return null;
        const d = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (d && (d._type === 'meeting_start' || d._type === 'meeting_end'
                   || d._type === 'meeting_started' || d._type === 'meeting_ended')) return d;
    } catch (_) {}
    return null;
}

// Detects ANY meeting message regardless of format
function isMeetingMessage(msg) {
    const t = msg.message_type;
    return t === 'meeting_start' || t === 'meeting_end'
        || t === 'meeting_started' || t === 'meeting_ended'
        || parseMeetingBody(msg) !== null;
}

function isMeetingStart(msg) {
    return msg.message_type === 'meeting_start' || msg.message_type === 'meeting_started';
}

function isMeetingEnd(msg) {
    return msg.message_type === 'meeting_end' || msg.message_type === 'meeting_ended';
}

function formatMeetingDuration(minutes) {
    if (!minutes || minutes <= 0) return '< 1 min';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h && m) return `${h}h ${m}m`;
    if (h)      return `${h} hour${h > 1 ? 's' : ''}`;
    return `${m} min`;
}

// Detects meeting platform from URL for styled join button
function parseMeetingPlatform(url) {
    if (!url) return null;
    try {
        const host = new URL(url).hostname.replace('www.', '').toLowerCase();
        if (host.includes('zoom.us') || host.includes('zoom.com'))      return { cls: 'zoom',    label: 'Join Zoom',   icon: '🎥' };
        if (host.includes('meet.google.com'))                            return { cls: 'gmeet',   label: 'Join Google Meet', icon: '📹' };
        if (host.includes('teams.microsoft.com') || host.includes('teams.live.com')) return { cls: 'teams', label: 'Join Teams', icon: '💻' };
        if (host.includes('webex.com'))                                  return { cls: 'webex',   label: 'Join Webex',  icon: '📡' };
    } catch (_) {}
    return { cls: 'generic', label: 'Join Meeting', icon: '🔗' };
}

// Parses EcoSystem plain-text "topic\nLink: url" format
function parseMeetingStartText(rawText) {
    if (!rawText) return { topic: '', link: null };
    const lines = rawText.split('\n');
    let link = null;
    const topicLines = [];
    for (const line of lines) {
        const m = line.match(/^Link:\s*(.+)$/i);
        if (m) { link = m[1].trim(); }
        else   { topicLines.push(line); }
    }
    return { topic: topicLines.join('\n').trim(), link };
}

// Builds the join button HTML for a meeting link
function buildJoinBtn(url) {
    if (!url) return '';
    const p = parseMeetingPlatform(url);
    if (!p) return '';
    return `<a href="${escHtml(url)}" target="_blank" rel="noopener noreferrer"
        class="mc-join-btn ${p.cls}" onclick="event.stopPropagation()">
        <span>${p.icon}</span>
        <span>${p.label}</span>
        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
    </a>`;
}

function createMeetingStartCard(msg, d) {
    const senderName = escHtml(msg.sender_name || 'Helpdesk');
    const initials   = senderName.substring(0, 1).toUpperCase();
    const timeStr    = formatFullDate(new Date(msg.created_at));

    // Resolve topic + link from either JSON body (new) or plain-text (EcoSystem)
    let topicText, meetingLink;
    if (d && (d.topic || d.meeting_link)) {
        topicText   = d.topic   || '';
        meetingLink = d.meeting_link || null;
    } else {
        const parsed = parseMeetingStartText(msg.message || '');
        topicText   = parsed.topic;
        meetingLink = parsed.link;
    }

    const topicHtml = topicText
        ? `<div class="mc-row"><svg class="w-3.5 h-3.5 text-violet-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>${escHtml(topicText)}</span></div>`
        : '';
    const joinBtnHtml = buildJoinBtn(meetingLink);

    return `<div class="flex gap-3 my-1">
        <div class="w-8 h-8 bg-violet-400 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900">${senderName}</span>
                <span class="text-xs text-gray-400">${timeStr}</span>
            </div>
            <div class="meeting-card-start">
                <div class="mc-header">
                    <svg class="w-4 h-4 text-violet-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <strong>Meeting Started</strong>
                </div>
                ${topicHtml}
                ${joinBtnHtml}
                <hr class="mc-divider">
                <div><span class="mc-badge">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    SLA Paused
                </span></div>
            </div>
        </div>
    </div>`;
}

function createMeetingEndCard(msg, d) {
    const senderName    = escHtml(msg.sender_name || 'Helpdesk');
    const initials      = senderName.substring(0, 1).toUpperCase();
    const timeStr       = formatFullDate(new Date(msg.created_at));
    const summaryText   = d.summary || msg.message || '';
    const summaryHtml   = summaryText
        ? `<div class="mc-row"><svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span style="white-space:pre-wrap">${escHtml(summaryText)}</span></div>`
        : '';
    const durationHtml  = d.duration_minutes != null
        ? `<div class="mc-row"><svg class="w-3.5 h-3.5 text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>Duration: <strong>${formatMeetingDuration(d.duration_minutes)}</strong></span></div>`
        : '';

    return `<div class="flex gap-3 my-1">
        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900">${senderName}</span>
                <span class="text-xs text-gray-400">${timeStr}</span>
            </div>
            <div class="meeting-card-end">
                <div class="mc-header">
                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <strong>Meeting Ended</strong>
                </div>
                ${durationHtml}
                ${summaryHtml}
                <hr class="mc-divider">
                <div><span class="mc-badge">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    SLA Resumed
                </span></div>
            </div>
        </div>
    </div>`;
}

function createMeetingCard(msg) {
    // Legacy format fallback
    const d = parseMeetingData(msg);
    if (!d) return null;

    const senderName = escHtml(msg.sender_name || 'Helpdesk');
    const timeStr    = formatFullDate(new Date(msg.created_at));
    const isEnded    = !!d.ended_at;
    const statusHtml = isEnded
        ? `<span class="meeting-status-ended">Ended</span>`
        : `<span class="meeting-status-ongoing">Ongoing</span>`;

    return `<div class="flex gap-3 my-1">
        <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${senderName.substring(0,1).toUpperCase()}</div>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900">${senderName}</span>
                <span class="text-xs text-gray-400">${timeStr}</span>
            </div>
            <div class="meeting-card">
                <div class="meeting-card-header"><span>📅</span><strong>${escHtml(d.title ?? 'Meeting')}</strong></div>
                <hr class="meeting-card-divider">
                <div>${statusHtml}</div>
            </div>
        </div>
    </div>`;
}

// ==================== MEETING ACTIONS ====================

async function loadActiveMeeting() {
    try {
        const res  = await fetch(`/tickets/${ticketId}/meeting/active`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (data.success) {
            activeMeeting = data.data;
            updateMeetingButton();
        }
    } catch (_) { /* silent */ }
}

function updateMeetingButton() {
    const btn   = document.getElementById('meetingBtn');
    const label = document.getElementById('meetingBtnLabel');
    if (!btn) return;

    if (activeMeeting) {
        btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition-all animate-pulse';
        btn.setAttribute('onclick', 'openMeetingEndModal()');
        if (label) label.textContent = 'End Meeting';
        btn.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>';
    } else {
        btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-100 text-violet-700 border border-violet-300 text-xs font-semibold rounded-lg hover:bg-violet-200 transition-all';
        btn.setAttribute('onclick', 'openMeetingStartModal()');
        if (label) label.textContent = 'Meeting';
        btn.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>';
    }
}

function openMeetingModal() {
    const modal = document.getElementById('meetingModal');
    const card  = document.getElementById('meetingModalCard');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => requestAnimationFrame(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }));
}

function closeMeetingModal() {
    const modal = document.getElementById('meetingModal');
    const card  = document.getElementById('meetingModalCard');
    if (!modal) return;
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

function openMeetingStartModal() {
    const topicInput = document.getElementById('meetingTopicInput');
    const linkInput  = document.getElementById('meetingLinkInput');
    if (topicInput) topicInput.value = '';
    if (linkInput)  linkInput.value  = '';
    document.getElementById('meetingPanelStart')?.classList.remove('hidden');
    document.getElementById('meetingPanelEnd')?.classList.add('hidden');
    openMeetingModal();
}

function openMeetingEndModal() {
    const summaryInput = document.getElementById('meetingSummaryInput');
    if (summaryInput) summaryInput.value = '';

    // Show elapsed duration
    if (activeMeeting?.started_at) {
        const elapsed = Math.round((Date.now() - new Date(activeMeeting.started_at)) / 60000);
        const durEl   = document.getElementById('meetingDurationLabel');
        const durText = document.getElementById('meetingDurationText');
        if (durEl && durText) {
            durText.textContent = `Elapsed: ${formatMeetingDuration(elapsed)}`;
            durEl.classList.remove('hidden');
        }
    }

    document.getElementById('meetingPanelStart')?.classList.add('hidden');
    document.getElementById('meetingPanelEnd')?.classList.remove('hidden');
    openMeetingModal();
}

async function doStartMeeting() {
    const topic       = document.getElementById('meetingTopicInput')?.value.trim() || '';
    const meetingLink = document.getElementById('meetingLinkInput')?.value.trim() || '';
    const btn         = document.getElementById('startMeetingConfirmBtn');
    const icon        = document.getElementById('startMeetingIcon');
    const spin        = document.getElementById('startMeetingSpinner');

    if (btn) btn.disabled = true;
    icon?.classList.add('hidden');
    spin?.classList.remove('hidden');

    try {
        const res  = await fetch(`/tickets/${ticketId}/meeting/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ topic, meeting_link: meetingLink }),
        });
        const data = await res.json();

        if (data.success) {
            activeMeeting = data.data;
            updateMeetingButton();
            closeMeetingModal();
            await loadMessages();
            showNotification('Meeting started. SLA clock paused.', 'success');
        } else {
            showNotification(data.message || 'Failed to start meeting.', 'error');
        }
    } catch (e) {
        showNotification('Error: ' + e.message, 'error');
    } finally {
        if (btn) btn.disabled = false;
        icon?.classList.remove('hidden');
        spin?.classList.add('hidden');
    }
}

async function doEndMeeting() {
    const summary = document.getElementById('meetingSummaryInput')?.value.trim() || '';
    const btn     = document.getElementById('endMeetingConfirmBtn');
    const icon    = document.getElementById('endMeetingIcon');
    const spin    = document.getElementById('endMeetingSpinner');

    if (btn) btn.disabled = true;
    icon?.classList.add('hidden');
    spin?.classList.remove('hidden');

    try {
        const res  = await fetch(`/tickets/${ticketId}/meeting/end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ summary }),
        });
        const data = await res.json();

        if (data.success) {
            activeMeeting = null;
            updateMeetingButton();
            closeMeetingModal();
            await loadMessages();
            const dur = formatMeetingDuration(data.data?.duration_minutes ?? 0);
            showNotification(`Meeting ended (${dur}). SLA clock resumed.`, 'success');
        } else {
            showNotification(data.message || 'Failed to end meeting.', 'error');
        }
    } catch (e) {
        showNotification('Error: ' + e.message, 'error');
    } finally {
        if (btn) btn.disabled = false;
        icon?.classList.remove('hidden');
        spin?.classList.add('hidden');
    }
}

// ==================== MESSAGE RENDERING ====================
function createMessageBubble(msg) {
    // Meeting messages (all formats: new JSON, EcoSystem plain-text, legacy parseMeetingData)
    if (isMeetingMessage(msg)) {
        const d = parseMeetingBody(msg) || {};
        if (isMeetingStart(msg)) return createMeetingStartCard(msg, d);
        if (isMeetingEnd(msg))   return createMeetingEndCard(msg, d);
    }

    // Legacy meeting format
    if (parseMeetingData(msg)) {
        return createMeetingCard(msg) ?? '';
    }

    const isEmployee = msg.sender_type === 'employee';
    const hasIdentity = !!(msg.sender_name || msg.sender_email);
    // Tangkap pesan sistem: sender_type='system' DAN channel bukan 'email'.
    // Email dengan sender_type='system' (CC reply dari pengirim tak terdaftar
    // yang disimpan processInbox()) adalah pesan manusia — render sebagai bubble.
    // Fallback regex untuk data lama yang tersimpan sebagai 'customer'.
    const isSystem = (msg.sender_type === 'system' && msg.channel !== 'email')
                  || /^Status change to "/i.test(msg.message || '');

    // System message → centered pill
    if (isSystem) {
        return `<div class="flex justify-center my-2">
            <span class="text-xs text-gray-500 bg-gray-100 border border-gray-200 px-3 py-1.5 rounded-full">${escHtml(msg.message)}</span>
        </div>`;
    }

    const initials = (msg.sender_name || '?').substring(0, 1).toUpperCase();
    const timeStr  = formatFullDate(new Date(msg.created_at));
    const isEmail  = msg.channel === 'email';

    // Channel badge
    const channelBadge = isEmail
        ? `<span class="msg-channel-badge msg-channel-email">
               <svg style="width:8px;height:8px" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
               Email</span>`
        : `<span class="msg-channel-badge msg-channel-web">
               <svg style="width:8px;height:8px" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/></svg>
               Web</span>`;

    // CC line
    const ccStr  = formatCcEmails(msg.cc_emails);
    const ccLine = ccStr
        ? `<span class="inline-flex items-center gap-1 text-[10px] text-gray-400 mt-0.5">
               <svg style="width:10px;height:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
               <span class="font-medium text-gray-500">CC:</span>
               <span>${escHtml(ccStr)}</span>
           </span>`
        : '';

    // Message body
    let msgContent;
    if (isEmail && msg.message_html) {
        // Email with HTML — render directly with scoped CSS (no iframe needed)
        msgContent = `<div class="message-content text-sm text-gray-700 email-html-body">${msg.message_html}</div>`;
    } else if (isEmployee) {
        // Employee web reply via Quill
        msgContent = `<div class="message-content text-sm text-gray-700">${msg.message_html || msg.message_body || msg.message || ''}</div>`;
    } else {
        // Customer plain text
        msgContent = `<p class="text-sm text-gray-700 whitespace-pre-wrap">${escHtml(msg.message || '')}</p>`;
    }

    // Attachments
    const isEmailWithHtml = isEmail && !!msg.message_html;
    const attachHtml = renderAttachments(msg.attachments, isEmailWithHtml);

    // Employee (agent/helpdesk) → LEFT side
    if (isEmployee) {
        return `
        <div class="flex gap-3">
            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
            <div>
                <div class="flex flex-col mb-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-gray-900">${escHtml(msg.sender_name || 'Unknown')}</span>
                        ${channelBadge}
                        <span class="text-xs text-gray-400">${timeStr}</span>
                    </div>
                    ${ccLine}
                </div>
                <div class="message-bubble bubble-employee p-3 inline-block text-left">
                    ${msgContent}
                    ${attachHtml}
                </div>
            </div>
        </div>`;
    }

    // Customer (self) or external → RIGHT side
    const avatarBg    = msg.sender_type === 'customer' ? 'bg-blue-600' : 'bg-blue-500';
    const statusHtml  = statusIndicator(msg);
    const statusSection = statusHtml ? `<div class="msg-status-row">${statusHtml}</div>` : '';
    return `
        <div class="flex gap-3 flex-row-reverse">
            <div class="w-8 h-8 ${avatarBg} rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
            <div class="text-right">
                <div class="flex flex-col mb-1 items-end">
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <span class="text-sm font-semibold text-gray-900">${escHtml(msg.sender_name || 'Unknown')}</span>
                        ${channelBadge}
                        <span class="text-xs text-gray-400">${timeStr}</span>
                    </div>
                    ${ccLine}
                </div>
                <div class="message-bubble bubble-customer p-3 inline-block text-left">
                    ${msgContent}
                    ${attachHtml}
                    ${statusSection}
                </div>
            </div>
        </div>`;
}

function renderAttachments(attachments, isEmailWithHtml = false) {
    if (!attachments || attachments.length === 0) return '';

    // Inline images already embedded in message_html — skip their thumbnails
    const files = isEmailWithHtml
        ? attachments.filter(a => !a.is_inline)
        : attachments;

    if (files.length === 0) return '';

    let html = '<div class="mt-2 space-y-1">';
    files.forEach(file => {
        const icon  = attachmentIcon(file.attachment_type, file.mime_type);
        const size  = formatFileSize(file.file_size);
        const isImg = file.mime_type?.startsWith('image/');
        const url   = escHtml(file.url || '#');
        html += `
            <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 max-w-xs">
                ${icon}
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-700 truncate">${escHtml(file.file_name)}</p>
                    ${size ? `<p class="text-[10px] text-gray-400">${size}</p>` : ''}
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    ${isImg ? `<a href="${url}" target="_blank" rel="noopener" class="text-xs text-blue-500 hover:underline">View</a>` : ''}
                    <a href="${url}" target="_blank" rel="noopener" class="text-xs text-blue-500 hover:underline">Download</a>
                </div>
            </div>`;
    });
    html += '</div>';
    return html;
}

// SVG icons (not emoji) — production server kadang tidak set Content-Type charset=utf-8
// sehingga emoji UTF-8 ter-render mojibake (e.g. "ÖŸ"). SVG aman dari masalah charset
// dan font emoji OS yang berbeda-beda.
function attachmentIcon(type, mime, sizeClass = 'w-5 h-5') {
    const cls = `${sizeClass} flex-shrink-0`;
    if (mime?.startsWith('image/')) return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>`;
    if (type === 'pdf')             return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>`;
    if (type === 'document')        return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/><path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>`;
    if (type === 'spreadsheet')     return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm10 1H7v3h6V5zm-6 4v3h2V9H7zm0 4v2h2v-2H7zm4 0v2h2v-2h-2zm0-1v-3h2v3h-2z" clip-rule="evenodd"/></svg>`;
    if (type === 'archive')         return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm5 1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm0 3a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm0 3a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm0 3a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>`;
    return `<svg xmlns="http://www.w3.org/2000/svg" class="${cls} text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/></svg>`;
}

function formatFileSize(bytes) {
    if (!bytes)           return '';
    if (bytes < 1024)     return bytes + ' B';
    if (bytes < 1048576)  return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// ==================== CHANNEL INDICATOR ====================
function updateChannelIndicator(emailThreadId, channel) {
    const el = document.getElementById('channelIndicator');
    if (!el) return;
    const emailIcon = `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>`;
    const chatIcon  = `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/></svg>`;
    if (emailThreadId || channel === 'email') {
        const text = channel === 'email'
            ? 'Replies will be sent to the support team via <strong>Email</strong>'
            : 'Email thread active — helpdesk replies will be sent to <strong>your email</strong>';
        el.innerHTML = `<div class="px-4 pt-2 flex items-center gap-1.5 text-xs text-blue-700">${emailIcon}<span>${text}</span></div>`;
    } else {
        el.innerHTML = `<div class="px-4 pt-2 flex items-center gap-1.5 text-xs text-gray-400">${chatIcon}<span>Replies only visible in <strong>Jarvies</strong> — no email will be sent</span></div>`;
    }
}

async function refreshIndicator() {
    try {
        const res  = await fetch(`/tickets/${ticketId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (data.success) {
            updateChannelIndicator(data.data.email_thread_id, data.data.channel);
        }
    } catch (_) { /* silent */ }
}

// ==================== SEND REPLY ====================
async function sendReply() {
    const commentHtml = quill.root.innerHTML;
    const comment     = quill.getText().trim();
    const hasFiles    = selectedFiles.length > 0;

    if (!comment && !hasFiles) {
        showNotification('Message cannot be empty.', 'error');
        return;
    }

    const btn     = document.getElementById('sendBtn');
    const icon    = document.getElementById('sendIcon');
    const spinner = document.getElementById('sendSpinner');
    if (btn) { btn.disabled = true; icon?.classList.add('hidden'); spinner?.classList.remove('hidden'); }

    // Selalu ambil CSRF token terbaru dari meta tag untuk hindari mismatch jika session di-refresh
    CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    try {
        let body, headers;
        if (hasFiles) {
            const fd = new FormData();
            fd.append('comment', comment);
            fd.append('comment_html', commentHtml);
            if (isEmailChannel) fd.append('cc_emails', JSON.stringify(ccEmails));
            selectedFiles.forEach(f => fd.append('attachments[]', f));
            body = fd;
            headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN };
        } else {
            body = JSON.stringify({
                comment,
                comment_html: commentHtml,
                ...(isEmailChannel ? { cc_emails: ccEmails } : {}),
            });
            headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN };
        }

        const res  = await fetch(`/tickets/${ticketId}/comment`, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body
        });

        // 419 = CSRF mismatch — session expired, minta user reload
        if (res.status === 419) {
            showNotification('Session expired. Page will reload automatically...', 'error');
            setTimeout(() => window.location.reload(), 2000);
            return;
        }

        const data = await res.json();

        if (data.success) {
            quill.setContents([]);
            resetAttachments();
            await loadMessages();
            refreshIndicator();
            showNotification('Message sent.', 'success');
        } else {
            showNotification(data.message || 'Failed to send message.', 'error');
        }
    } catch (err) {
        showNotification('Error: ' + err.message, 'error');
    } finally {
        if (btn) { btn.disabled = false; icon?.classList.remove('hidden'); spinner?.classList.add('hidden'); }
    }
}

// ==================== SIDEBAR: Ticket List ====================
async function loadSidebarTickets() {
    try {
        const res  = await fetch('/tickets/ajax/fetch', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin'
        });
        const data = await res.json();
        document.getElementById('sidebarLoading')?.remove();

        if (data.data) {
            allSidebarTickets = data.data.sort((a, b) => {
                const dA = a.last_message_at || a.created_at;
                const dB = b.last_message_at || b.created_at;
                return new Date(dB) - new Date(dA);
            });
            renderSidebarTickets(allSidebarTickets);
        }
    } catch {
        document.getElementById('sidebarLoading')?.remove();
    }
}

function renderSidebarTickets(tickets) {
    const list = document.getElementById('sidebarTicketList');
    if (!list) return;

    if (tickets.length === 0) {
        list.innerHTML = '<p class="text-gray-500 text-xs text-center py-4">No tickets found.</p>';
        return;
    }

    const prioClsMap = {
        'Very High': 'sb-prio-very-high',
        'High':      'sb-prio-high',
        'Medium':    'sb-prio-medium',
        'Low':       'sb-prio-low',
    };
    const prioLabelMap = { 'Very High': 'V.High', 'High': 'High', 'Medium': 'Med', 'Low': 'Low' };
    const statusClsMap = {
        'open':                    'sb-jstat-open',
        'in process':              'sb-jstat-in-process',
        'waiting on customer':     'sb-jstat-wait-customer',
        'waiting on 3rd party':    'sb-jstat-wait-3rdparty',
        'waiting to confirmation': 'sb-jstat-wait-confirm',
        'hold':                    'sb-jstat-hold',
        'cancelled':               'sb-jstat-cancelled',
        'closed':                  'sb-jstat-closed',
    };
    const statusLabelMap = {
        'open':                    'Open',
        'in process':              'In Process',
        'waiting on customer':     'Wait Customer',
        'waiting on 3rd party':    'Wait 3rd Party',
        'waiting to confirmation': 'Wait Confirm',
        'hold':                    'Hold',
        'cancelled':               'Cancelled',
        'closed':                  'Closed',
    };

    list.innerHTML = tickets.map(t => {
        const isActive   = t.ticket_id === ticketId;
        const ticketNum  = t.ticket_number || ('#' + t.ticket_id);
        const desc       = t.description || 'No description';
        const lastDate   = t.last_message_at || t.created_at;
        const timeAgo    = formatTimeAgo(new Date(lastDate));

        const prio      = t.ticket_priority || 'Medium';
        const prioCls   = prioClsMap[prio] || 'sb-prio-medium';
        const prioKey   = prioLabelMap[prio] || prio;

        const stat      = (t.status || 'open').toLowerCase();
        const sCls      = statusClsMap[stat] || 'sb-jstat-default';
        const sLabel    = statusLabelMap[stat] || stat;

        const unreadDot = (!isActive && t.has_unread)
            ? `<span class="ml-1 inline-block w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 self-center"></span>`
            : '';

        return `
            <a href="/tickets/${t.ticket_id}" class="sidebar-ticket-item ${isActive ? 'active' : ''}" title="${escHtml(ticketNum)}">
                <div class="flex items-start justify-between gap-1 mb-0.5">
                    <span class="text-[11px] font-bold text-gray-800 truncate leading-tight">${escHtml(ticketNum)}</span>
                    <div class="flex items-center gap-1 flex-shrink-0 mt-0.5">
                        ${unreadDot}
                        <span class="text-[9px] text-gray-400">${timeAgo}</span>
                    </div>
                </div>
                <p class="text-[10px] text-gray-500 truncate mb-1.5 leading-snug">${escHtml(desc)}</p>
                <div class="flex items-center gap-1 flex-wrap">
                    <span class="sb-badge ${prioCls}">${prioKey}</span>
                    <span class="sb-badge ${sCls}">${sLabel}</span>
                </div>
            </a>`;
    }).join('');
}

function filterSidebarTickets() {
    const term = document.getElementById('sidebarSearch')?.value.toLowerCase() || '';
    if (!term) { renderSidebarTickets(allSidebarTickets); return; }
    const filtered = allSidebarTickets.filter(t =>
        (t.ticket_number && t.ticket_number.toLowerCase().includes(term)) ||
        (t.description && t.description.toLowerCase().includes(term)) ||
        (t.status && t.status.toLowerCase().includes(term))
    );
    renderSidebarTickets(filtered);
}

// ==================== HELPERS ====================

// Ekstrak alamat email dari raw CC value (string, object, atau JSON string)
function normalizeCcAddr(c) {
    if (!c) return '';
    if (typeof c === 'string') return c.trim().toLowerCase();
    if (typeof c === 'object') return String(c.address || c.email || '').trim().toLowerCase();
    return '';
}

// Merge CC dari message baru (selain dari customer sendiri) ke state ccEmails.
// Dipanggil saat polling deteksi message baru dari helpdesk/employee/system.
// Exclude Jarvies customer email + helpdesk sender agar tidak CC ke diri sendiri.
function mergeAgentCcFromMessages(newMessages) {
    if (!Array.isArray(newMessages) || newMessages.length === 0) return;
    const selfEmail = @json(strtolower(session('user.email') ?? ''));
    const helpdeskSelf = @json(strtolower(env('MS_SENDER_EMAIL') ?? ''));
    const excludeSet = new Set([selfEmail, helpdeskSelf].filter(Boolean));
    const existingSet = new Set(ccEmails.map(e => String(e).toLowerCase()));
    let changed = false;
    for (const msg of newMessages) {
        // Hanya ambil CC dari message NON-customer — customer = user Jarvies sendiri
        if (msg.sender_type === 'customer') continue;
        let raw = msg.cc_emails;
        if (typeof raw === 'string' && raw) {
            try { raw = JSON.parse(raw); } catch (_) { raw = []; }
        }
        if (!Array.isArray(raw)) continue;
        for (const c of raw) {
            const addr = normalizeCcAddr(c);
            if (!addr) continue;
            if (excludeSet.has(addr)) continue;
            if (existingSet.has(addr)) continue;
            ccEmails.push(addr);
            existingSet.add(addr);
            changed = true;
        }
    }
    if (changed) renderCcTags();
}

// Normalize cc_emails: bisa berupa string "a@b.com, c@d.com"
// atau JSON array [{"name":null,"address":"a@b.com"}]
function formatCcEmails(cc) {
    if (!cc) return '';
    if (Array.isArray(cc)) {
        return cc.map(item => (typeof item === 'string' ? item : (item.address || item.email || ''))).filter(Boolean).join(', ');
    }
    if (typeof cc === 'string') {
        try {
            const parsed = JSON.parse(cc);
            if (Array.isArray(parsed)) {
                return parsed.map(item => (typeof item === 'string' ? item : (item.address || item.email || ''))).filter(Boolean).join(', ');
            }
        } catch {}
        return cc; // sudah berupa string biasa
    }
    return String(cc);
}

// Format: "13 Mar 2026, 11:59 (WIB)"
function formatFullDate(date) {
    try {
        return date.toLocaleString('en-GB', {
            timeZone: 'Asia/Jakarta',
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false
        }) + ' (WIB)';
    } catch {
        // Fallback for older browsers that don't support timeZone option
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const dd   = String(date.getUTCDate()).padStart(2, '0');
        const mon  = months[date.getUTCMonth()];
        const yyyy = date.getUTCFullYear();
        const hh   = String(date.getUTCHours()).padStart(2, '0');
        const mm   = String(date.getUTCMinutes()).padStart(2, '0');
        return `${dd} ${mon} ${yyyy}, ${hh}:${mm} (WIB)`;
    }
}

// Format relatif untuk sidebar ticket list: "3m ago", "2d ago"
function formatTimeAgo(date) {
    const diff = Date.now() - date.getTime();
    const m    = Math.floor(diff / 60000);
    const h    = Math.floor(diff / 3600000);
    const d    = Math.floor(diff / 86400000);
    if (m < 1)  return 'now';
    if (m < 60) return m + 'm ago';
    if (h < 24) return h + 'h ago';
    if (d < 7)  return d + 'd ago';
    return date.toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short' });
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showNotification(msg, type = 'info') {
    const bg = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const el = document.createElement('div');
    el.className = `fixed top-4 right-4 ${bg} text-white px-5 py-3 rounded-xl shadow-xl z-[100] text-sm font-medium`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 350); }, 3000);
}

// ==================== PANEL TOGGLES ====================
function toggleTicketRightPanel() {
    const panel        = document.getElementById('ticketRightPanel');
    const iconCollapse = document.getElementById('rightPanelIconCollapse');
    const iconExpand   = document.getElementById('rightPanelIconExpand');
    if (!panel) return;
    const isExpanded = panel.style.width !== '0px';
    panel.style.width    = isExpanded ? '0px'   : '288px';
    panel.style.opacity  = isExpanded ? '0'     : '1';
    panel.style.overflow = isExpanded ? 'hidden' : '';
    if (iconCollapse) iconCollapse.classList.toggle('hidden', !isExpanded);
    if (iconExpand)   iconExpand.classList.toggle('hidden', isExpanded);
}

function toggleReplyBox() {
    const inner    = document.getElementById('replyComposeInner');
    const iconDown = document.getElementById('replyToggleIconDown');
    const iconUp   = document.getElementById('replyToggleIconUp');
    if (!inner) return;
    const isExpanded = inner.style.maxHeight !== '0px';
    inner.style.maxHeight = isExpanded ? '0px'   : '600px';
    inner.style.opacity   = isExpanded ? '0'     : '1';
    if (iconDown) iconDown.classList.toggle('hidden', isExpanded);
    if (iconUp)   iconUp.classList.toggle('hidden', !isExpanded);
}

// ==================== CONFIRMATION MODAL ====================
function showConfirmModal({ title, message, info, infoCls, iconHtml, iconBg, btnCls, btnLabel, onConfirm }) {
    document.getElementById('confirmModalTitle').textContent   = title;
    document.getElementById('confirmModalMessage').textContent = message;

    const iconWrap = document.getElementById('confirmModalIconWrap');
    iconWrap.className = `w-16 h-16 rounded-full flex items-center justify-center mb-4 ${iconBg}`;
    iconWrap.innerHTML = iconHtml;

    const infoEl = document.getElementById('confirmModalInfo');
    if (info) {
        infoEl.innerHTML = info;
        infoEl.className = `mx-6 mt-3 mb-1 p-3 rounded-xl text-xs leading-relaxed ${infoCls || ''}`;
    } else {
        infoEl.className = 'hidden';
    }

    const btnEl = document.getElementById('confirmModalBtn');
    btnEl.className  = `flex-1 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all ${btnCls}`;
    btnEl.textContent = btnLabel;
    btnEl.onclick     = () => { closeConfirmModal(); onConfirm(); };

    const modal = document.getElementById('confirmModal');
    const card  = document.getElementById('confirmModalCard');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => requestAnimationFrame(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }));
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    const card  = document.getElementById('confirmModalCard');
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

// ==================== CUSTOMER ACTIONS ====================
function customerCloseTicket() {
    showConfirmModal({
        title:    'Close This Ticket?',
        message:  'Closing this ticket indicates your issue has been resolved. A confirmation email will be sent to you.',
        info:     '<div class="flex items-start gap-2"><svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>Once closed, this ticket will be marked as resolved. Open a new ticket if the issue recurs.</span></div>',
        infoCls:  'bg-green-50 text-green-700',
        iconHtml: '<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
        iconBg:   'bg-green-100',
        btnCls:   'bg-green-600 hover:bg-green-700 active:bg-green-800',
        btnLabel: 'Yes, Close Ticket',
        onConfirm: doCloseTicket,
    });
}

async function doCloseTicket() {
    try {
        const res = await fetch(`/tickets/${ticketId}/close`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed to close ticket.', 'error');
        }
    } catch (e) {
        showToast('An error occurred.', 'error');
    }
}

function customerCancelTicket() {
    showConfirmModal({
        title:    'Cancel This Ticket?',
        message:  'This action cannot be undone. The ticket will be permanently cancelled.',
        info:     '<div class="flex items-start gap-2"><svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg><span>A cancellation email will be sent to you. Please open a new ticket if you still need assistance.</span></div>',
        infoCls:  'bg-red-50 text-red-700',
        iconHtml: '<svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
        iconBg:   'bg-red-100',
        btnCls:   'bg-red-600 hover:bg-red-700 active:bg-red-800',
        btnLabel: 'Yes, Cancel Ticket',
        onConfirm: doCancelTicket,
    });
}

async function doCancelTicket() {
    try {
        const res = await fetch(`/tickets/${ticketId}/cancel`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed to cancel ticket.', 'error');
        }
    } catch (e) {
        showToast('An error occurred.', 'error');
    }
}

// ==================== STATUS UPDATE (Admin) ====================

const statusColors = {
    'open':                    { bg: 'bg-sky-100',    text: 'text-sky-700',    border: 'border-sky-400',    ring: 'ring-sky-400',    dot: 'bg-sky-500 border-sky-500' },
    'in process':              { bg: 'bg-blue-100',   text: 'text-blue-700',   border: 'border-blue-400',   ring: 'ring-blue-400',   dot: 'bg-blue-500 border-blue-500' },
    'waiting on customer':     { bg: 'bg-amber-100',  text: 'text-amber-700',  border: 'border-amber-400',  ring: 'ring-amber-400',  dot: 'bg-amber-500 border-amber-500' },
    'waiting on 3rd party':    { bg: 'bg-indigo-100', text: 'text-indigo-700', border: 'border-indigo-400', ring: 'ring-indigo-400', dot: 'bg-indigo-500 border-indigo-500' },
    'waiting to confirmation': { bg: 'bg-purple-100', text: 'text-purple-700', border: 'border-purple-400', ring: 'ring-purple-400', dot: 'bg-purple-500 border-purple-500' },
    'hold':                    { bg: 'bg-orange-100', text: 'text-orange-700', border: 'border-orange-400', ring: 'ring-orange-400', dot: 'bg-orange-500 border-orange-500' },
    'cancelled':               { bg: 'bg-red-100',    text: 'text-red-700',    border: 'border-red-400',    ring: 'ring-red-400',    dot: 'bg-red-500 border-red-500' },
    'closed':                  { bg: 'bg-green-100',  text: 'text-green-700',  border: 'border-green-400',  ring: 'ring-green-400',  dot: 'bg-green-500 border-green-500' },
};

let _selectedStatus = '{{ $ticket->status ?? "open" }}';

function selectStatus(val) {
    _selectedStatus = val;
    document.querySelectorAll('.status-radio-btn').forEach(btn => {
        const s = btn.dataset.status;
        const dot = btn.querySelector('.status-dot');
        const c = statusColors[s] || {};
        if (s === val) {
            btn.className = `status-radio-btn w-full text-left px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all ${c.bg} ${c.text} ${c.border} ring-1 ${c.ring}`;
            if (dot) dot.className = `w-3 h-3 rounded-full border-2 flex-shrink-0 status-dot ${c.dot}`;
        } else {
            btn.className = 'status-radio-btn w-full text-left px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-all bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50';
            if (dot) dot.className = 'w-3 h-3 rounded-full border-2 flex-shrink-0 status-dot border-gray-300';
        }
    });
}

async function saveStatus() {
    const btn     = document.getElementById('saveStatusBtn');
    const icon    = document.getElementById('saveStatusIcon');
    const spinner = document.getElementById('saveStatusSpinner');

    btn.disabled = true;
    icon.classList.add('hidden');
    spinner.classList.remove('hidden');

    try {
        const res = await fetch(`/tickets/${ticketId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ status: _selectedStatus }),
        });
        const data = await res.json();
        if (data.success) {
            showNotification('Status berhasil diperbarui.', 'success');
        } else {
            showNotification(data.message || 'Gagal memperbarui status.', 'error');
        }
    } catch (e) {
        showNotification('Terjadi kesalahan.', 'error');
    } finally {
        btn.disabled = false;
        icon.classList.remove('hidden');
        spinner.classList.add('hidden');
    }
}

</script>
@endsection
