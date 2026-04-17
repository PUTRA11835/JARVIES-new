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

@section('title', 'Initial — ' . Str::limit($staging->description, 40))
@section('page-title', 'Support Ticket')
@section('page-subtitle', 'Initial — ' . Str::limit($staging->description, 50))

@push('styles')
<style>
/* Sidebar ticket items */
.sidebar-ticket-item {
    display: block; padding: 8px 10px 8px 12px; border-radius: 7px;
    transition: background .15s, border-color .15s, box-shadow .15s; text-decoration: none;
    background: rgba(255,255,255,.92); border: 1px solid rgba(255,255,255,.5);
    border-left: 3px solid transparent; box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.sidebar-ticket-item:hover { background: rgba(255,255,255,1); border-left-color: #b91c1c; box-shadow: 0 2px 6px rgba(0,0,0,.12); }
.sidebar-ticket-item.active { background: rgba(255,255,255,1); border-left-color: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,.15); }

/* Message bubbles */
.message-bubble { max-width: 85%; word-break: break-word; }
.bubble-customer { background: #eff6ff; border-radius: 12px 12px 4px 12px; }

/* Message content */
.message-content p             { margin-bottom: .25rem; }
.message-content p:last-child  { margin-bottom: 0; }
.message-content ul, .message-content ol { padding-left: 1.5rem; margin-bottom: .5rem; }
.message-content blockquote { border-left: 3px solid #d1d5db; padding-left: .75rem; color: #6b7280; }
.message-content a    { color: #2563eb; text-decoration: underline; }
.message-content img  { max-width: 100%; height: auto; border-radius: 4px; }
.message-content h1, .message-content h2, .message-content h3 { font-weight: 600; margin-bottom: .25rem; }
</style>
@endpush

@section('content')
@php
    $priorityColors = [
        'Low'       => 'bg-green-50 text-green-700 border-green-200',
        'Medium'    => 'bg-blue-50 text-blue-700 border-blue-200',
        'High'      => 'bg-red-50 text-red-700 border-red-200',
        'Very High' => 'bg-red-100 text-red-800 border-red-300',
    ];
    $customerName = session('user.company_name') ?? session('user.name') ?? session('user.email') ?? 'Customer';
    $bodyHtml        = $staging->body ?? '';
    // Gambar sudah disimpan sebagai file lokal dengan URL — tampilkan langsung
    $bodyHtmlDisplay = $bodyHtml;
    $hasBody         = trim(strip_tags($bodyHtmlDisplay)) !== '';
    $ccList          = json_decode($staging->cc_emails ?? '[]', true) ?? [];
    $attachmentNames = json_decode($staging->attachment_names ?? '[]', true) ?? [];
@endphp

<div class="flex gap-6" style="height: calc(100vh - 140px); min-height: 500px;">

    {{-- ═══ MAIN: Conversation Thread ═══ --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-w-0">

        {{-- Ticket Header --}}
        <div class="px-6 py-4 border-b border-gray-200 shrink-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <h2 class="text-base font-bold text-gray-900">{{ $staging->description ?: 'No description' }}</h2>
                <span class="text-sm text-gray-400 font-mono">—</span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-500">
                    Initial
                </span>
                @if($staging->ticket_priority)
                <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold border {{ $priorityColors[$staging->ticket_priority] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                    {{ $staging->ticket_priority }}
                </span>
                @endif
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500 flex-wrap">
                <span class="font-medium text-gray-700">{{ $customerName }}</span>
                <span class="text-gray-300">|</span>
                <span>{{ $staging->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                <span class="text-gray-300">|</span>
                <span class="inline-flex items-center gap-1 text-amber-600 font-medium">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Awaiting validation
                </span>
            </div>
        </div>

        {{-- Messages Thread --}}
        <div id="messagesThread" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">

            {{-- Info banner --}}
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>Your ticket is being reviewed by our support team. It will appear as an active ticket once validated. You will be notified via email.</p>
            </div>

            {{-- Initial message bubble --}}
            <div class="flex gap-3 flex-row-reverse">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center shrink-0 text-white text-xs font-bold mt-0.5">
                    {{ strtoupper(substr($customerName, 0, 1)) }}
                </div>
                <div class="text-right">
                    <div class="flex flex-col mb-1 items-end">
                        <div class="flex items-center gap-2 flex-wrap justify-end">
                            <span class="text-sm font-semibold text-gray-900">{{ $customerName }}</span>
                            <span class="text-[10px] bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-semibold">Initial</span>
                            <span class="text-xs text-gray-400">{{ $staging->created_at->format('d M Y, H:i') }} (WIB)</span>
                        </div>
                        @if(count($ccList) > 0)
                        <span class="inline-flex items-center gap-1 text-[10px] text-gray-400 mt-0.5">
                            <svg style="width:10px;height:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium text-gray-500">CC:</span>
                            <span>{{ implode(', ', $ccList) }}</span>
                        </span>
                        @endif
                    </div>
                    <div class="message-bubble bubble-customer p-3 inline-block text-left">
                        @if($hasBody)
                            <div class="message-content text-sm text-gray-700">
                                {!! $bodyHtmlDisplay !!}
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No message body provided.</p>
                        @endif

                        {{-- File attachments: tampilkan download links --}}
                        @if(count($attachmentNames) > 0)
                        <div class="mt-3 pt-3 border-t border-blue-100 space-y-1">
                            @foreach($attachmentNames as $fileName)
                            @php
                                $safeName    = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $fileName);
                                $localPath   = storage_path('app/staging-attachments/' . $staging->id . '/' . $safeName);
                                $canDownload = file_exists($localPath);
                            @endphp
                            @if($canDownload)
                            <a href="{{ route('tickets.staging.attachment.download', ['id' => $staging->id, 'filename' => $fileName]) }}"
                               download="{{ $fileName }}"
                               class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                {{ $fileName }}
                            </a>
                            @endif
                            @endforeach
                        </div>
                        @endif

                        {{-- Additional info block --}}
                        @if($staging->name || $staging->no_hp || $staging->module || $staging->client)
                        <div class="mt-3 pt-3 border-t border-blue-100 space-y-1 text-xs text-gray-600">
                            @if($staging->name)
                            <div class="flex gap-2"><span class="text-gray-400 w-16 shrink-0">Name</span><span>{{ $staging->name }}</span></div>
                            @endif
                            @if($staging->no_hp)
                            <div class="flex gap-2"><span class="text-gray-400 w-16 shrink-0">Phone</span><span>{{ $staging->no_hp }}</span></div>
                            @endif
                            @if($staging->module)
                            <div class="flex gap-2"><span class="text-gray-400 w-16 shrink-0">Module</span><span>{{ $staging->module }}</span></div>
                            @endif
                            @if($staging->client)
                            <div class="flex gap-2"><span class="text-gray-400 w-16 shrink-0">Client</span><span>{{ $staging->client }}</span></div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div class="flex items-center gap-3 py-2">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-xs text-gray-400 whitespace-nowrap">Waiting for support team response</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

        </div>

        {{-- Compose Area — disabled --}}
        <div class="border-t border-gray-200 shrink-0">
            <div class="px-4 pt-2 flex items-center gap-1.5 text-xs text-gray-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                </svg>
                <span>Replies will be available once your ticket has been validated by our team.</span>
            </div>
            <div class="px-4 pt-2 pb-3">
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-400 italic select-none cursor-not-allowed min-h-20 flex items-center">
                    Type your reply here...
                </div>
                <div class="flex items-center justify-end mt-2">
                    <button disabled
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-gray-300 text-white text-xs font-semibold rounded-lg cursor-not-allowed opacity-60">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
                        </svg>
                        Send Reply
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ RIGHT: Properties ═══ --}}
    <div class="hidden xl:block w-72 bg-white rounded-xl border border-gray-200 shadow-sm overflow-y-auto shrink-0">
        <div class="p-5">
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wide mb-4">Properties</h4>
            <div class="space-y-3">

                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Ticket No.</label>
                    <p class="text-xs text-gray-400 italic px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                        Pending assignment
                    </p>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Status</label>
                    <p class="text-xs px-2.5 py-1.5 bg-gray-100 text-gray-500 rounded-lg border border-gray-200 font-medium">
                        Initial
                    </p>
                </div>

                @if($staging->ticket_priority)
                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Priority</label>
                    <p class="text-xs px-2.5 py-1.5 rounded-lg border font-medium {{ $priorityColors[$staging->ticket_priority] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                        {{ $staging->ticket_priority }}
                    </p>
                </div>
                @endif

                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block">Ticket Type</label>
                    <p class="text-xs text-gray-400 italic px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">—</p>
                </div>



                <div class="pt-3 border-t border-gray-100 space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Customer</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">{{ $customerName }}</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Start Date</label>
                        <p class="text-xs text-gray-400 italic px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">—</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Due Date</label>
                        <p class="text-xs text-gray-400 italic px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">—</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">Created</label>
                        <p class="text-xs text-gray-700 px-2.5 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $staging->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const STAGING_ID = {{ $staging->id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
let allSidebarTickets = [];

document.addEventListener('DOMContentLoaded', function () {
    loadSidebarTickets();
});

// ==================== SIDEBAR ====================
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
        // Staging item: aktif jika staging_id cocok
        const isStaging = t.is_staging === true;
        const isActive  = isStaging
            ? (t.staging_id === STAGING_ID)
            : false;

        const href      = isStaging
            ? `/tickets/staging/${t.staging_id}`
            : `/tickets/${t.ticket_id}`;

        const desc      = t.description || 'No description';
        const shortDesc = desc.length > 40 ? desc.substring(0, 40) + '…' : desc;
        const lastDate  = t.last_message_at || t.created_at;
        const timeAgo   = formatTimeAgo(new Date(lastDate));
        const prioColors = {
            'Very High': 'bg-purple-500', 'High': 'bg-red-400',
            'Medium': 'bg-blue-400', 'Low': 'bg-green-400'
        };
        const prioDot   = prioColors[t.ticket_priority] || 'bg-gray-400';
        const ticketNum = isStaging ? '#pending' : (t.ticket_number || ('#' + t.ticket_id));

        return `
            <a href="${href}" class="sidebar-ticket-item ${isActive ? 'active' : ''}">
                <div class="flex items-center justify-between mb-0.5">
                    <span class="text-xs font-semibold text-gray-800 truncate max-w-[140px]">${escHtml(ticketNum)}</span>
                    <span class="text-[10px] text-gray-400 shrink-0 ml-1">${timeAgo}</span>
                </div>
                <p class="text-[11px] text-gray-500 truncate mb-1">${escHtml(shortDesc)}</p>
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full ${prioDot} shrink-0"></div>
                    <span class="text-[10px] text-gray-400">${isStaging ? 'Initial' : (t.ticket_priority || 'Medium')}</span>
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
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str ?? '');
    return d.innerHTML;
}

function formatTimeAgo(date) {
    const diff = Math.floor((Date.now() - date) / 1000);
    if (diff < 60)    return diff + 's ago';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}
</script>
@endpush
