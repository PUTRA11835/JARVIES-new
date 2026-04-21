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

/* Quill message content */
.message-content p             { margin-bottom: 0.25rem; }
.message-content p:last-child  { margin-bottom: 0; }
.message-content ul,
.message-content ol            { padding-left: 1.5rem; margin-bottom: 0.5rem; }
.message-content blockquote    { border-left: 3px solid #d1d5db; padding-left: 0.75rem; color: #6b7280; }
.message-content a             { color: #2563eb; text-decoration: underline; }
.message-content img           { max-width: 100%; height: auto; border-radius: 4px; }
.message-content h1, .message-content h2, .message-content h3 { font-weight: 600; margin-bottom: 0.25rem; }
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
    $agentName = 'Unassigned';
    if ($ticket->employee) {
        $fn = $ticket->employee->basicData->first_name ?? '';
        $ln = $ticket->employee->basicData->last_name ?? '';
        $agentName = trim($fn . ' ' . $ln) ?: 'Assigned';
    }
    $customerName = $ticket->customer?->basicData?->name_1 ?? session('user.company_name') ?? 'Customer';
@endphp

<div class="flex gap-6" style="height: calc(100vh - 140px); min-height: 500px;">

    {{-- ═══ MAIN: Conversation Thread ═══ --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-w-0">

        {{-- Ticket Header --}}
        <div class="px-6 py-4 border-b border-gray-200 shrink-0">
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

            {{-- Channel mode indicator (diupdate JS setelah reply) --}}
            <div id="channelIndicator">
            @if($ticket->email_thread_id || $ticket->channel === 'email')
            <div class="px-4 pt-2 flex items-center gap-1.5 text-xs text-blue-700">
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
            <div class="px-4 pt-2 flex items-center gap-1.5 text-xs text-gray-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                </svg>
                <span>Replies only visible in <strong>Jarvies</strong> — no email will be sent</span>
            </div>
            @endif
            </div>

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

    {{-- ═══ RIGHT: Properties (read-only) ═══ --}}
    <div class="hidden xl:block w-72 bg-white rounded-xl border border-gray-200 shadow-sm overflow-y-auto shrink-0">
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

                {{-- Jarvies Status --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Status</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200 capitalize">
                        {{ $ticket->jarvies_status ?? 'in process' }}
                    </p>
                </div>

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

                {{-- Divider --}}
                <div class="pt-3 border-t border-gray-100 space-y-3">
                    {{-- Customer --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Customer</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $customerName }}
                        </p>
                    </div>

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
                </div>


            </div>
        </div>
    </div>

</div>


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
        const icon = file.type.startsWith('image/') ? '🖼️'
                   : file.type === 'application/pdf' ? '📄'
                   : /\.(doc|docx)$/i.test(file.name) ? '📝'
                   : /\.(xls|xlsx|csv)$/i.test(file.name) ? '📊'
                   : /\.(zip|rar)$/i.test(file.name) ? '🗜️' : '📎';
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

// ==================== MESSAGE RENDERING ====================
function createMessageBubble(msg) {
    const isEmployee = msg.sender_type === 'employee';
    const hasIdentity = !!(msg.sender_name || msg.sender_email);
    const isSystem    = msg.sender_type === 'system' && !hasIdentity;

    // System message → centered pill
    if (isSystem) {
        return `<div class="flex justify-center my-2">
            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">${escHtml(msg.message)}</span>
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
    const avatarBg = msg.sender_type === 'customer' ? 'bg-blue-600' : 'bg-blue-500';
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
                <span class="text-lg flex-shrink-0">${icon}</span>
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

function attachmentIcon(type, mime) {
    if (mime?.startsWith('image/'))  return '🖼️';
    if (type === 'pdf')              return '📄';
    if (type === 'document')         return '📝';
    if (type === 'spreadsheet')      return '📊';
    if (type === 'archive')          return '🗜️';
    return '📎';
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

    list.innerHTML = tickets.map(t => {
        const isActive  = t.ticket_id === ticketId;
        const desc      = t.description || 'No description';
        const shortDesc = desc.length > 40 ? desc.substring(0, 40) + '…' : desc;
        const lastDate  = t.last_message_at || t.created_at;
        const timeAgo   = formatTimeAgo(new Date(lastDate));
        const prioColors = {
            'Very High': 'bg-purple-500',
            'High':      'bg-red-400',
            'Medium':    'bg-blue-400',
            'Low':       'bg-green-400'
        };
        const prioDot  = prioColors[t.ticket_priority] || 'bg-gray-400';
        const ticketNum = t.ticket_number || ('#' + t.ticket_id);

        return `
            <a href="/tickets/${t.ticket_id}" class="sidebar-ticket-item ${isActive ? 'active' : ''}">
                <div class="flex items-center justify-between mb-0.5">
                    <span class="text-xs font-semibold text-gray-800 truncate max-w-[140px]">${escHtml(ticketNum)}</span>
                    <span class="text-[10px] text-gray-400 shrink-0 ml-1">${timeAgo}</span>
                </div>
                <p class="text-[11px] text-gray-500 truncate mb-1">${escHtml(shortDesc)}</p>
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full ${prioDot} shrink-0"></div>
                    <span class="text-[10px] text-gray-400">${t.ticket_priority || 'Medium'}</span>
                </div>
            </a>`;
    }).join('');
}

function filterSidebarTickets() {
    const term = document.getElementById('sidebarSearch')?.value.toLowerCase() || '';
    if (!term) { renderSidebarTickets(allSidebarTickets); return; }
    const filtered = allSidebarTickets.filter(t =>
        (t.ticket_number && t.ticket_number.toLowerCase().includes(term)) ||
        (t.description && t.description.toLowerCase().includes(term))
    );
    renderSidebarTickets(filtered);
}

// ==================== HELPERS ====================

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


</script>
@endsection
