@extends('layouts.app')

@section('sidebar-width', 'w-72')

@section('sidebar-nav')
    {{-- Header --}}
    <div class="px-4 pt-4 pb-3 border-b border-red-700/50 shrink-0">
        <a href="{{ route('tickets.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded-lg text-white/80 hover:bg-white/10 transition-all text-sm font-medium w-full">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Back to Tickets</span>
        </a>
        {{-- Search --}}
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

    {{-- User Info --}}
    <div class="p-4 border-t border-red-700/50 shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                <span class="text-sm font-semibold">{{ strtoupper(substr(session('user.name', 'U'), 0, 1)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate text-white">{{ session('user.name', 'User') }}</p>
                <p class="text-xs text-red-300 truncate">{{ session('user.role.name', 'Role') }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="p-1.5 hover:bg-white/10 rounded-lg transition-colors" title="Logout">
                    <svg class="w-4 h-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
@endsection

@section('title', ($ticket->ticket_number ?? 'Pending') . ' — Ticket')
@section('page-title', 'Support Ticket')
@section('page-subtitle', ($ticket->ticket_number ?? 'Pending') . ' — ' . Str::limit($ticket->description ?? '', 50))

@push('styles')
<style>
/* Sidebar ticket items */
.sidebar-ticket-item {
    display: block; padding: 8px 10px 8px 12px; border-radius: 7px;
    transition: background 0.15s, border-color 0.15s; text-decoration: none;
    background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.07);
    border-left: 3px solid transparent;
}
.sidebar-ticket-item:hover { background: rgba(255,255,255,0.1); border-left-color: rgba(255,255,255,0.3); }
.sidebar-ticket-item.active { background: rgba(255,255,255,0.16); border-left-color: rgba(255,255,255,0.75); }

/* Message bubbles */
.message-bubble { max-width: 75%; word-break: break-word; }
.bubble-customer { background: #fff1f2; border-radius: 18px 4px 18px 18px; }
.bubble-employee { background: #f3f4f6; border-radius: 4px 18px 18px 18px; }

/* Quill HTML content */
.quill-content p { margin-bottom: 0.25rem; }
.quill-content p:last-child { margin-bottom: 0; }
.quill-content a { color: #2563eb; text-decoration: underline; }
.quill-content ul, .quill-content ol { padding-left: 1.5rem; margin-bottom: 0.5rem; }
.quill-content blockquote { border-left: 3px solid #d1d5db; padding-left: 0.75rem; color: #6b7280; margin: 0.25rem 0; }
.quill-content img { max-width: 100%; height: auto; border-radius: 4px; }
.quill-content h1, .quill-content h2, .quill-content h3 { font-weight: 600; margin-bottom: 0.25rem; }

/* Email iframe */
.email-frame { width: 100%; min-height: 60px; border: none; display: block; }
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
                <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                @if($agentName !== 'Unassigned')
                <span class="text-gray-300">|</span>
                <span>Agent: <span class="font-medium">{{ $agentName }}</span></span>
                @endif
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
        <div class="border-t border-gray-200 shrink-0 bg-white">
            <textarea id="replyInput" rows="4"
                placeholder="Type your reply here..."
                class="w-full px-4 py-3 text-sm text-gray-700 placeholder-gray-400 border-none focus:outline-none focus:ring-0 resize-none bg-white"></textarea>
            <div class="flex items-center justify-between px-4 pb-3 pt-1 border-t border-gray-100">
                <div class="flex items-center gap-1">
                    <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        Attachment
                    </button>
                </div>
                <button onclick="sendReply()" id="sendBtn"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-700 text-white text-sm font-semibold rounded-lg hover:bg-red-800 active:bg-red-900 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                    </svg>
                    Send Reply
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ RIGHT: Properties (read-only) ═══ --}}
    <div class="hidden xl:block w-64 bg-white rounded-xl border border-gray-200 shadow-sm overflow-y-auto shrink-0">
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

                {{-- Agent (PIC) --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Agent (PIC)</label>
                    <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                        {{ $agentName }}
                    </p>
                </div>

                {{-- Team Members --}}
                @if($ticket->members->count() > 0)
                <div class="pt-3 border-t border-gray-100">
                    <label class="text-xs font-semibold text-gray-500 mb-2 block">Team Members</label>
                    <div class="space-y-1">
                        @foreach($ticket->members as $member)
                        @php
                            $mName = trim(($member->basicData->first_name ?? '') . ' ' . ($member->basicData->last_name ?? ''));
                            if (empty($mName)) $mName = 'Member';
                        @endphp
                        <div class="px-2.5 py-1.5 bg-blue-50 rounded-lg">
                            <span class="text-xs text-blue-700 font-medium">{{ $mName }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

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
                            {{ $ticket->start_date ? \Carbon\Carbon::parse($ticket->start_date)->format('M d, Y') : '—' }}
                        </p>
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Due Date</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->end_date ? \Carbon\Carbon::parse($ticket->end_date)->format('M d, Y') : '—' }}
                        </p>
                    </div>

                    {{-- Created --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Created</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $ticket->created_at->format('M d, Y h:i A') }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
const ticketId   = {{ $ticket->ticket_id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

let renderedMessageIds = new Set();
let allSidebarTickets  = [];

document.addEventListener('DOMContentLoaded', function () {
    loadMessages();
    loadSidebarTickets();
    startPolling();
});

// ==================== POLLING ====================
function startPolling() {
    setInterval(() => loadMessages(), 15000);
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

        if (!data.success || !data.data || data.data.length === 0) {
            if (renderedMessageIds.size === 0)
                thread.innerHTML = `<div class="flex items-center justify-center py-10 text-gray-400 text-sm">Belum ada pesan. Mulai percakapan dengan mengirim balasan.</div>`;
            return;
        }

        const messages    = data.data;
        const isFirstLoad = renderedMessageIds.size === 0;
        const newMessages = messages.filter(m => !renderedMessageIds.has(m.id));

        if (newMessages.length === 0) return;

        if (isFirstLoad) {
            thread.innerHTML = messages.map(m => createMessageBubble(m)).join('');
            messages.forEach(m => renderedMessageIds.add(m.id));
        } else {
            newMessages.forEach(m => {
                thread.insertAdjacentHTML('beforeend', createMessageBubble(m));
                renderedMessageIds.add(m.id);
            });
        }

        setupEmailFrames();
        thread.scrollTop = thread.scrollHeight;

    } catch (err) {
        if (loading) loading.style.display = 'none';
        if (renderedMessageIds.size === 0)
            thread.innerHTML = `<div class="text-center py-8 text-red-400 text-sm">Gagal memuat pesan.</div>`;
    }
}

// ==================== MESSAGE RENDERING ====================
function createMessageBubble(msg) {
    // Di JARVIES (customer portal):
    // - employee → KIRI (support team)
    // - semua non-employee (customer, external email, CC person) → KANAN (customer side)
    const isEmployee  = msg.sender_type === 'employee';
    // Hanya centered pill jika benar-benar system notification tanpa identitas pengirim
    const hasIdentity = !!(msg.sender_name || msg.sender_email);
    const isSystem    = msg.sender_type === 'system' && !hasIdentity;
    // Semua non-employee & non-system = tampil sebagai customer (KANAN)
    const isCustomer  = !isEmployee && !isSystem;
    const initials    = (msg.sender_name || '?').substring(0, 1).toUpperCase();
    const timeStr     = formatTimeAgo(new Date(msg.created_at));
    const isEmail     = msg.channel === 'email';

    // System message tanpa pengirim → centered pill
    if (isSystem) {
        return `<div class="flex justify-center my-2">
            <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">${escHtml(msg.message)}</span>
        </div>`;
    }

    // Channel badge
    const channelBadge = isEmail
        ? `<span class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">
               <svg style="width:8px;height:8px" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
               Email</span>`
        : `<span class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-green-100 text-green-700">
               <svg style="width:8px;height:8px" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/></svg>
               Web</span>`;

    // CC line — cc_emails bisa berupa string "a@b.com" atau JSON array [{address:"a@b.com"}]
    const ccStr = formatCcEmails(msg.cc_emails);
    const ccLine = ccStr
        ? `<div class="text-[10px] text-gray-400 mt-1 ${isEmployee ? '' : 'text-right'}">CC: ${escHtml(ccStr)}</div>`
        : '';

    // Attachments
    const attachHtml = (msg.attachments && msg.attachments.length)
        ? `<div class="flex flex-wrap gap-1.5 mt-2">
            ${msg.attachments.map(a => `
                <a href="${escHtml(a.url || '#')}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1 px-2 py-1 bg-white border border-gray-200 rounded-lg text-[11px] text-gray-600 hover:text-red-700 hover:border-red-200 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    ${escHtml(a.file_name)}
                </a>`).join('')}
           </div>`
        : '';

    // Message body
    // - Email channel       → tampilkan 'message' (plain text sudah di-strip email processor)
    //                         untuk bubble compact — sama seperti tampilan EcoSystem
    // - Web + employee      → message_html / message berisi Quill HTML → render as HTML
    // - Web + customer/ext  → plain text dari textarea JARVIES → escape
    let msgBody;
    if (isEmail) {
        // Gunakan plain text agar bubble compact dan tidak ada whitespace berlebih dari iframe
        const plainText = (msg.message || '').trim();
        if (plainText) {
            msgBody = `<p class="text-sm text-gray-800 whitespace-pre-wrap">${escHtml(plainText)}</p>`;
        } else if (msg.message_html) {
            // Fallback: iframe hanya jika tidak ada plain text sama sekali
            msgBody = `<iframe class="email-frame" data-srcdoc="${encodeURIComponent(msg.message_html)}"
                sandbox="allow-same-origin" scrolling="no"
                style="width:100%;min-height:30px;border:none;display:block;"></iframe>`;
        } else {
            msgBody = `<p class="text-sm text-gray-400 italic">(pesan kosong)</p>`;
        }
    } else {
        if (isEmployee) {
            // Employee/helpdesk reply via Quill editor → message field berisi HTML
            msgBody = `<div class="quill-content text-sm text-gray-800">${msg.message_html || msg.message || ''}</div>`;
        } else {
            // Customer atau external email sender → plain text
            msgBody = `<p class="text-sm text-gray-800 whitespace-pre-wrap">${escHtml(msg.message || '')}</p>`;
        }
    }

    // Employee              → KIRI,  avatar biru,   bubble abu
    // Customer (sender_type=customer) → KANAN, avatar merah,  bubble merah muda
    // External/CC email    → KANAN, avatar ungu, bubble merah muda
    const avatarBg  = isEmployee
        ? 'bg-blue-600'
        : (msg.sender_type === 'customer' ? 'bg-red-600' : 'bg-purple-600');
    const bubbleCls = isEmployee ? 'bubble-employee' : 'bubble-customer';

    return `
        <div class="flex gap-3 items-start ${isEmployee ? '' : 'flex-row-reverse'}">
            <div class="w-8 h-8 ${avatarBg} rounded-full flex items-center justify-center shrink-0 text-white text-xs font-bold mt-0.5">${initials}</div>
            <div class="max-w-[75%] ${isEmployee ? '' : 'flex flex-col items-end'}">
                <div class="flex items-center gap-2 mb-1 flex-wrap ${isEmployee ? '' : 'justify-end'}">
                    <span class="text-sm font-semibold text-gray-900">${escHtml(msg.sender_name || 'Unknown')}</span>
                    ${channelBadge}
                    <span class="text-xs text-gray-400">${timeStr}</span>
                </div>
                ${ccLine}
                <div class="message-bubble ${bubbleCls} px-4 py-2.5">
                    ${msgBody}
                    ${attachHtml}
                </div>
            </div>
        </div>`;
}

function setupEmailFrames() {
    document.querySelectorAll('iframe.email-frame').forEach(frame => {
        if (frame.dataset.initialized) return;
        frame.dataset.initialized = '1';
        frame.srcdoc = decodeURIComponent(frame.dataset.srcdoc || '');
        frame.addEventListener('load', () => {
            try {
                const h = frame.contentDocument?.documentElement?.scrollHeight;
                if (h) frame.style.minHeight = Math.min(h + 20, 600) + 'px';
            } catch {}
        }, { once: true });
    });
}

// ==================== SEND REPLY ====================
async function sendReply() {
    const input   = document.getElementById('replyInput');
    const comment = input.value.trim();

    if (!comment) {
        showNotification('Pesan tidak boleh kosong.', 'error');
        return;
    }

    const btn = document.getElementById('sendBtn');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-60'); }

    try {
        const res  = await fetch(`/tickets/${ticketId}/comment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            credentials: 'same-origin',
            body: JSON.stringify({ comment })
        });
        const data = await res.json();

        if (data.success) {
            input.value = '';
            await loadMessages();
            showNotification('Pesan terkirim.', 'success');
        } else {
            showNotification(data.message || 'Gagal mengirim pesan.', 'error');
        }
    } catch (err) {
        showNotification('Error: ' + err.message, 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-60'); }
    }
}

// Ctrl+Enter untuk kirim
document.getElementById('replyInput')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        sendReply();
    }
});

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
            allSidebarTickets = data.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
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
        list.innerHTML = '<p class="text-white/40 text-xs text-center py-4">No tickets found.</p>';
        return;
    }

    list.innerHTML = tickets.map(t => {
        const isActive  = t.ticket_id === ticketId;
        const desc      = t.description || 'No description';
        const shortDesc = desc.length > 38 ? desc.substring(0, 38) + '…' : desc;
        const timeAgo   = formatTimeAgo(new Date(t.created_at));
        const prioColors = { Low: 'bg-green-400', Medium: 'bg-blue-400', High: 'bg-red-400' };
        const prioDot   = prioColors[t.ticket_priority] || 'bg-gray-400';
        const ticketNum = t.ticket_number || 'Pending';

        return `
            <a href="/tickets/${t.ticket_id}" class="sidebar-ticket-item ${isActive ? 'active' : ''}">
                <div class="flex items-center justify-between mb-0.5">
                    <span class="text-xs font-semibold text-white truncate max-w-[150px]">${escHtml(ticketNum)}</span>
                    <span class="text-[10px] text-white/50 shrink-0 ml-1">${timeAgo}</span>
                </div>
                <p class="text-[11px] text-white/70 truncate mb-1">${escHtml(shortDesc)}</p>
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full ${prioDot}"></div>
                    <span class="text-[10px] text-white/50">${t.ticket_priority || '—'}</span>
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

function formatTimeAgo(date) {
    const diff = Date.now() - date.getTime();
    const m    = Math.floor(diff / 60000);
    const h    = Math.floor(diff / 3600000);
    const d    = Math.floor(diff / 86400000);
    if (m < 1)  return 'now';
    if (m < 60) return m + 'm ago';
    if (h < 24) return h + 'h ago';
    if (d < 7)  return d + 'd ago';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
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
