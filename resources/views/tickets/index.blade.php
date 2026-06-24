@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Support Tickets')
@section('page-subtitle', 'Manage and track all support requests')

@section('header-actions')
<div class="flex items-center gap-2">
    @if(session('user.role.id') === 3)
    <a href="{{ route('tickets.pending') }}" class="flex items-center space-x-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors border border-yellow-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Submission History</span>
    </a>
    @endif
    @if(session('user.role.id') === 3)
    <a href="{{ route('tickets.create') }}" class="flex items-center space-x-2 bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>New Ticket</span>
    </a>
    @endif
</div>
@endsection

@section('content')
@php
    $userRole        = session('user.role.id', 3);
    $userId          = session('user.id', 0);
    $isAdmin         = $userRole === 1;
    $isCustomerAdmin = $userRole === 3 && session('user.can_view_all_tickets', false);
    $userEmail       = session('user.email', '');

@endphp

{{-- Status Filter Cards --}}
<div class="mb-4">
    <button onclick="toggleSection('statsSection', 'statsChevron')"
            class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors duration-150 select-none mb-2 group">
        <svg id="statsChevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
             class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-transform duration-200">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
        <span class="uppercase tracking-wide">Status Info</span>
    </button>
    <div id="statsSection" class="overflow-hidden transition-all duration-300" style="max-height: 200px;">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 pb-2">
            <div id="filterAll" class="bg-white rounded-xl border-2 border-red-600 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('all')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total</p>
                <p class="text-2xl font-bold text-gray-900" id="totalCount">0</p>
            </div>
            <div id="filterOpen" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('open')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Open</p>
                <p class="text-2xl font-bold text-sky-600" id="openCount">0</p>
            </div>
            <div id="filterInprocess" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('in process')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">In Process</p>
                <p class="text-2xl font-bold text-blue-600" id="processCount">0</p>
            </div>
            <div id="filterWaitingCustomer" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('waiting on customer')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Wait Customer</p>
                <p class="text-2xl font-bold text-amber-600" id="waitingCustomerCount">0</p>
            </div>
            <div id="filterWaiting3rdParty" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('waiting on 3rd party')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Wait 3rd Party</p>
                <p class="text-2xl font-bold text-indigo-600" id="waiting3rdPartyCount">0</p>
            </div>
            <div id="filterWaitingConfirmation" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('waiting to confirmation')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Wait Confirm</p>
                <p class="text-2xl font-bold text-purple-600" id="waitingConfirmationCount">0</p>
            </div>
            <div id="filterHold" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('hold')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Hold</p>
                <p class="text-2xl font-bold text-orange-600" id="holdCount">0</p>
            </div>
            <div id="filterClosed" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-200 cursor-pointer" onclick="filterTickets('closed')">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Closed</p>
                <p class="text-2xl font-bold text-green-600" id="closedCount">0</p>
            </div>
        </div>
    </div>
</div>


{{-- Pagination --}}
<div class="flex items-center justify-between mb-4">
    <span class="text-sm text-gray-500">
        <span id="currentRangeStart">1</span>-<span id="currentRangeEnd">20</span>
        of <span id="totalItems">0</span> tickets
    </span>
    <div class="flex items-center gap-1">
        <button onclick="previousPage()" id="btnPrevPage" disabled
                class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </button>
        <button onclick="nextPage()" id="btnNextPage"
                class="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </button>
    </div>
</div>

{{-- Ticket Table --}}
<div id="ticketsContainer" class="hidden">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-auto" style="max-height: calc(100vh - 380px); min-height: 200px;">
            <table class="w-full text-sm border-collapse" style="min-width: 900px;">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr>
                        {{-- Last Update: sortable --}}
                        <th onclick="sortTickets('last_update')"
                            class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200 sticky left-0 bg-gray-50 z-20 cursor-pointer select-none hover:bg-gray-100 transition-colors"
                            style="min-width:110px;">
                            <div class="flex items-center gap-1">
                                <span>Last Update</span>
                                <span id="sort-icon-last_update" class="text-red-500 font-bold">↓</span>
                            </div>
                        </th>

                        {{-- Ticket: sort + filter panel --}}
                        <th class="p-0 text-left whitespace-nowrap border-b border-gray-200 sticky bg-gray-50 z-20 relative" style="min-width:120px; left:110px;">
                            <button type="button" onclick="toggleColPanel('ticket', event)"
                                    class="w-full flex items-center gap-1.5 px-3 py-2.5 hover:bg-gray-100 transition-colors">
                                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Ticket</span>
                                <svg id="ticketCaret" class="w-3 h-3 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                <svg id="ticketFilterIcon" class="w-3 h-3 text-gray-300 transition-colors shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 011 1v1.586a1 1 0 01-.293.707l-4.121 4.121A1 1 0 0012 12.121V15.5l-4 1.5v-4.879a1 1 0 00-.293-.707L3.586 7.293A1 1 0 013.293 6.586L3 5z" clip-rule="evenodd"/></svg>
                                <span id="sort-icon-ticket_number" class="text-gray-300 font-bold text-xs">⇅</span>
                            </button>
                            <div id="ticketPanel" class="col-filter-panel hidden fixed bg-white rounded-xl shadow-2xl border border-gray-100 z-[9999] p-3" style="min-width:230px;">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Search ticket no.</label>
                                <input type="text" id="ticketFilterInput" placeholder="e.g. TKT-001…"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400"
                                       oninput="colFilters.ticketNumber=this.value; applyColFilters();" onclick="event.stopPropagation()">
                                <div class="border-t border-gray-100 mt-3 pt-2">
                                    <span class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Sort</span>
                                    <div class="flex gap-2">
                                        <button type="button" id="ticketSortAsc" onclick="setTicketSort('asc')"
                                                class="flex-1 px-2 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">↑ Ascending</button>
                                        <button type="button" id="ticketSortDesc" onclick="setTicketSort('desc')"
                                                class="flex-1 px-2 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">↓ Descending</button>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" onclick="clearTicketFilter()" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50">Clear</button>
                                    <button type="button" onclick="closeColPanel('ticket')" class="px-3 py-1.5 text-xs text-white bg-red-700 hover:bg-red-800 rounded-md">Done</button>
                                </div>
                            </div>
                        </th>

                        {{-- Description: text search panel --}}
                        <th class="p-0 text-left whitespace-nowrap border-b border-gray-200 bg-gray-50 relative" style="min-width:280px;">
                            <button type="button" onclick="toggleColPanel('desc', event)"
                                    class="w-full flex items-center gap-1.5 px-3 py-2.5 hover:bg-gray-100 transition-colors">
                                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Description</span>
                                <svg id="descCaret" class="w-3 h-3 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                <svg id="descFilterIcon" class="w-3 h-3 text-gray-300 transition-colors shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 011 1v1.586a1 1 0 01-.293.707l-4.121 4.121A1 1 0 0012 12.121V15.5l-4 1.5v-4.879a1 1 0 00-.293-.707L3.586 7.293A1 1 0 013.293 6.586L3 5z" clip-rule="evenodd"/></svg>
                            </button>
                            <div id="descPanel" class="col-filter-panel hidden fixed bg-white rounded-xl shadow-2xl border border-gray-100 z-[9999] p-3" style="min-width:260px;">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Search description</label>
                                <input type="text" id="descFilterInput" placeholder="Type keyword…"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400"
                                       oninput="colFilters.description=this.value; applyColFilters();" onclick="event.stopPropagation()">
                                <p class="text-[10px] text-gray-400 mt-1.5">Matches any ticket whose description contains this text.</p>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" onclick="clearColFilter('description','descFilterInput')" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50">Clear</button>
                                    <button type="button" onclick="closeColPanel('desc')" class="px-3 py-1.5 text-xs text-white bg-red-700 hover:bg-red-800 rounded-md">Done</button>
                                </div>
                            </div>
                        </th>

                        {{-- Date: date range panel --}}
                        <th class="p-0 text-left whitespace-nowrap border-b border-gray-200 bg-gray-50 relative" style="min-width:100px;">
                            <button type="button" onclick="toggleColPanel('date', event)"
                                    class="w-full flex items-center gap-1.5 px-3 py-2.5 hover:bg-gray-100 transition-colors">
                                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Date</span>
                                <svg id="dateCaret" class="w-3 h-3 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                <svg id="dateFilterIcon" class="w-3 h-3 text-gray-300 transition-colors shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 011 1v1.586a1 1 0 01-.293.707l-4.121 4.121A1 1 0 0012 12.121V15.5l-4 1.5v-4.879a1 1 0 00-.293-.707L3.586 7.293A1 1 0 013.293 6.586L3 5z" clip-rule="evenodd"/></svg>
                            </button>
                            <div id="datePanel" class="col-filter-panel hidden fixed bg-white rounded-xl shadow-2xl border border-gray-100 z-[9999] p-3" style="min-width:240px;">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">From</label>
                                    <input type="date" id="dateFilterFrom" onclick="event.stopPropagation()"
                                           class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400">
                                </div>
                                <div class="mt-2">
                                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">To</label>
                                    <input type="date" id="dateFilterTo" onclick="event.stopPropagation()"
                                           class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400">
                                </div>
                                <p id="dateFilterError" class="hidden text-xs text-red-500 mt-1">"To" must be on/after "From".</p>
                                <div class="flex justify-end gap-2 mt-3">
                                    <button type="button" onclick="clearDateColFilter()" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50">Clear</button>
                                    <button type="button" onclick="applyDateColFilter()" class="px-3 py-1.5 text-xs text-white bg-red-700 hover:bg-red-800 rounded-md">Apply</button>
                                </div>
                            </div>
                        </th>

                        {{-- Status --}}
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200" style="min-width:150px;">Status</th>

                        {{-- Priority --}}
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200" style="min-width:100px;">Priority</th>

                        {{-- Type --}}
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200" style="min-width:130px;">Type</th>

                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200" style="min-width:100px;">Man Days</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-b border-gray-200" style="min-width:130px;">Created By</th>
                    </tr>
                </thead>
                <tbody id="ticketsListBody" class="divide-y divide-gray-100 bg-white"></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Loading State --}}
<div id="loadingState" class="text-center py-20 bg-white rounded-xl border border-gray-200">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 rounded-full mb-4">
        <svg class="animate-spin h-10 w-10 text-red-800" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
    <p class="text-gray-600 text-lg font-semibold">Loading tickets...</p>
</div>

{{-- Empty State --}}
<div id="emptyState" class="hidden text-center py-20 bg-white rounded-xl border border-gray-200">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    </div>
    <p class="text-gray-700 text-xl font-bold mb-2">No tickets found</p>
    <p class="text-gray-500 text-sm mb-6">Try adjusting your filters or search criteria</p>
    <button onclick="resetFilters()" class="inline-flex items-center gap-2 px-6 py-3 bg-red-800 text-white text-sm font-bold rounded-xl hover:bg-red-900 transition-colors">
        Clear All Filters
    </button>
</div>

@endsection

@push('styles')
<style>
/* Collapsible sections */
#statsSection {
    transition: max-height 0.25s ease, opacity 0.2s ease;
}
#statsSection[style*="max-height: 0"] { opacity: 0; }
#statsChevron { transition: transform 0.2s ease; }

/* Table rows */
#ticketsListBody tr { cursor: pointer; transition: background 0.15s; }
#ticketsListBody tr:hover { background: #fafafa; }

/* Unread row — blue (agent has replied, awaiting customer read) */
#ticketsListBody tr.ticket-unread {
    background: #f0f7ff;
}
#ticketsListBody tr.ticket-unread:hover {
    background: #e6f0fd;
}
#ticketsListBody tr.ticket-unread td:first-child {
    border-left: 3px solid #93c5fd;
    padding-left: 10px;
}
#ticketsListBody tr.ticket-unread td:first-child,
#ticketsListBody tr.ticket-unread td:nth-child(2) {
    background: #f0f7ff;
}
#ticketsListBody tr.ticket-unread:hover td:first-child,
#ticketsListBody tr.ticket-unread:hover td:nth-child(2) {
    background: #e6f0fd;
}

/* Unread dot */
.unread-dot {
    display: inline-block;
    width: 7px; height: 7px;
    border-radius: 50%;
    vertical-align: middle;
    margin-right: 5px;
    flex-shrink: 0;
    background: #3b82f6;
    box-shadow: 0 0 0 2px #dbeafe;
}

/* Sticky columns shadow */
#ticketsListBody tr td:first-child,
#ticketsListBody tr td:nth-child(2) {
    z-index: 5;
    box-shadow: 2px 0 4px rgba(0,0,0,0.04);
}
#ticketsListBody tr:hover td:first-child,
#ticketsListBody tr:hover td:nth-child(2) { background: #fafafa; }

/* Stat card active */
.stat-card-active { border-color: #991b1b !important; border-width: 2px !important; }

/* Column filter panels */
.col-filter-panel {
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
}
</style>
@endpush

@push('scripts')
<script>
// ==================== STATE ====================
let allTickets      = [];
let filteredTickets = [];
let currentFilter   = 'all';
let ticketScope     = 'mine';   // 'mine' | 'all'  (only relevant for customer admin)
let currentPage     = 1;
let itemsPerPage    = 20;
let totalItems      = 0;
let totalPages      = 0;

let colFilters  = { ticketNumber: '', description: '', dateFrom: '', dateTo: '' };
let sortField   = 'last_update';
let sortDir     = 'desc';
let openPanel   = null;

const FETCH_URL         = '{{ route("tickets.ajax.fetch") }}';
const CSRF_TOKEN        = '{{ csrf_token() }}';
const IS_CUSTOMER_ADMIN = {{ $isCustomerAdmin ? 'true' : 'false' }};
const USER_EMAIL        = '{{ $userEmail }}';

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', function() {
    loadTickets();
    startPolling();

    const urlParams = new URLSearchParams(window.location.search);
    const openId = parseInt(urlParams.get('open'));
    if (openId) window.location.href = `/tickets/${openId}`;
});

// ==================== POLLING ====================
function startPolling() {
    setInterval(async () => {
        try {
            const res = await fetch(FETCH_URL, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                credentials: 'same-origin'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success || !data.data) return;

            const fresh = data.data.sort((a, b) =>
                new Date(b.last_message_at || b.created_at) - new Date(a.last_message_at || a.created_at)
            );

            const hasChanges = fresh.length !== allTickets.length ||
                fresh.some(t => {
                    const e = allTickets.find(x => x.ticket_id === t.ticket_id);
                    if (!e) return true;
                    return (t.last_message_at || '') !== (e.last_message_at || '');
                });

            if (!hasChanges) return;
            allTickets = fresh;
            updateStats();
            applyColFilters();
        } catch {}
    }, 30000);
}

// ==================== LOAD ====================
async function loadTickets() {
    try {
        showLoading(true);
        const res = await fetch(FETCH_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin'
        });
        if (res.status === 401) { window.location.href = '{{ route("login") }}'; return; }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (data.success && data.data) {
            allTickets = data.data;
            updateStats();
            applyColFilters();
        } else {
            showEmpty();
        }
    } catch (err) {
        showToast('Failed to load tickets: ' + err.message, 'error');
        showEmpty();
    }
}

// ==================== STATS ====================
function updateStats() {
    const base = getScopedTickets();
    document.getElementById('totalCount').textContent              = base.length;
    document.getElementById('openCount').textContent               = base.filter(t => t.status === 'open').length;
    document.getElementById('processCount').textContent            = base.filter(t => t.status === 'in process').length;
    document.getElementById('waitingCustomerCount').textContent    = base.filter(t => t.status === 'waiting on customer').length;
    document.getElementById('waiting3rdPartyCount').textContent    = base.filter(t => t.status === 'waiting on 3rd party').length;
    document.getElementById('waitingConfirmationCount').textContent= base.filter(t => t.status === 'waiting to confirmation').length;
    document.getElementById('holdCount').textContent               = base.filter(t => t.status === 'hold').length;
    document.getElementById('closedCount').textContent             = base.filter(t => t.status === 'closed').length;
}

// ==================== RENDER ====================
function renderTickets() {
    showLoading(false);
    totalItems = filteredTickets.length;
    totalPages = Math.ceil(totalItems / itemsPerPage);

    if (totalItems === 0) { showEmpty(); updatePagination(); return; }

    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('ticketsContainer').classList.remove('hidden');

    const start = (currentPage - 1) * itemsPerPage;
    const end   = Math.min(start + itemsPerPage, totalItems);
    document.getElementById('ticketsListBody').innerHTML =
        filteredTickets.slice(start, end).map(t => createTicketRow(t)).join('');
    updatePagination();
}

function createTicketRow(ticket) {
    const isStaging = ticket.is_staging === true;
    const href      = isStaging ? `/tickets/staging/${ticket.staging_id}` : `/tickets/${ticket.ticket_id}`;

    const lastActivity = new Date(ticket.last_message_at || ticket.created_at);
    const createdAt    = new Date(ticket.created_at);
    const fmt   = d => d.toLocaleDateString('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric' });
    const fmtDT = d => d.toLocaleString('en-GB',    { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });

    const lastUpdateStr   = relativeTime(lastActivity);
    const lastUpdateTitle = fmtDT(lastActivity);
    const dateStr         = fmt(createdAt);

    // Unread: agent replied more recently than customer's last reply
    const lastAgent    = ticket.last_agent_reply_at  ? new Date(ticket.last_agent_reply_at)  : null;
    const lastCustomer = ticket.last_customer_reply_at ? new Date(ticket.last_customer_reply_at) : null;
    const hasUnread    = !isStaging && (ticket.has_unread || (lastAgent && (!lastCustomer || lastAgent > lastCustomer)));

    const unreadCls   = hasUnread ? 'ticket-unread' : '';
    const dot         = hasUnread ? '<span class="unread-dot" title="New reply from Helpdesk"></span>' : '';
    const timeColor   = hasUnread ? 'text-blue-600 font-semibold' : 'text-gray-500';
    const numColor    = hasUnread ? 'text-blue-700' : 'text-gray-800';

    const statusMap = {
        'open':                    { label: 'Open',                  cls: 'bg-sky-50 text-sky-700' },
        'in process':              { label: 'In Process',            cls: 'bg-blue-50 text-blue-700' },
        'waiting on customer':     { label: 'Waiting on Customer',   cls: 'bg-amber-50 text-amber-700' },
        'waiting on 3rd party':    { label: 'Waiting on 3rd Party',  cls: 'bg-indigo-50 text-indigo-700' },
        'waiting to confirmation': { label: 'Waiting Confirmation',  cls: 'bg-purple-50 text-purple-700' },
        'hold':                    { label: 'Hold',                  cls: 'bg-orange-50 text-orange-700' },
        'cancelled':               { label: 'Cancelled',             cls: 'bg-red-50 text-red-700' },
        'closed':                  { label: 'Closed',                cls: 'bg-green-50 text-green-700' },
    };
    const priorityMap = {
        'Very High': 'bg-purple-100 text-purple-700',
        'High':      'bg-red-100 text-red-700',
        'Medium':    'bg-blue-100 text-blue-700',
        'Low':       'bg-green-100 text-green-700',
    };
    const typeMap = {
        'Incident':       'bg-red-50 text-red-600',
        'Service Request':'bg-indigo-50 text-indigo-600',
        'Change Request': 'bg-amber-50 text-amber-600',
        'Consult':        'bg-teal-50 text-teal-600',
    };

    const jInfo       = isStaging
        ? { label: 'Awaiting Validation', cls: 'bg-gray-100 text-gray-500 italic' }
        : (statusMap[ticket.status] || { label: ticket.status || '—', cls: 'bg-gray-100 text-gray-500' });
    const priorityCls = priorityMap[ticket.ticket_priority] || 'bg-gray-100 text-gray-500';
    const typeCls     = typeMap[ticket.ticket_type] || 'bg-gray-100 text-gray-500';
    const agentName   = ticket.employee?.employee_name || '<span class="text-gray-400 text-xs">Unassigned</span>';

    const badge = (label, cls) => `<span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold ${cls}">${label}</span>`;

    return `<tr class="${unreadCls}" onclick="window.location='${href}'">
        <td class="px-3 py-2.5 whitespace-nowrap sticky left-0 bg-white" title="${lastUpdateTitle}">
            ${dot}<span class="text-xs ${timeColor}">${lastUpdateStr}</span>
        </td>
        <td class="px-3 py-2.5 whitespace-nowrap sticky bg-white border-r border-gray-100" style="left:110px;">
            <span class="font-mono text-xs font-semibold ${numColor}">${ticket.ticket_number || (isStaging ? '(initial)' : '—')}</span>
        </td>
        <td class="px-3 py-2.5 text-sm text-gray-700" style="min-width:280px;max-width:360px;">
            <span class="block truncate" title="${escapeHtml(ticket.description || '')}">${escapeHtml(ticket.description || '—')}</span>
            ${ticket.end_customer_name ? `<span class="block text-xs text-gray-400 mt-0.5">&#8627; ${escapeHtml(ticket.end_customer_name)}</span>` : ''}
        </td>
        <td class="px-3 py-2.5 text-sm text-gray-600 whitespace-nowrap">${dateStr}</td>
        <td class="px-3 py-2.5 whitespace-nowrap">${badge(jInfo.label, jInfo.cls)}</td>
        <td class="px-3 py-2.5 whitespace-nowrap">${ticket.ticket_priority ? badge(ticket.ticket_priority, priorityCls) : '<span class="text-gray-300 text-xs">—</span>'}</td>
        <td class="px-3 py-2.5 whitespace-nowrap">${ticket.ticket_type ? badge(ticket.ticket_type, typeCls) : '<span class="text-gray-300 text-xs">—</span>'}</td>
        <td class="px-3 py-2.5 text-sm text-gray-600 whitespace-nowrap">${ticket.approved_mandays != null ? ticket.approved_mandays.toFixed(2): '<span class="text-gray-300">—</span>'}</td>
        <td class="px-3 py-2.5 text-sm text-gray-600 whitespace-nowrap">${escapeHtml(ticket.submitted_by_name || ticket.submitted_by_email || '—')}</td>
    </tr>`;
}

// ==================== PAGINATION ====================
function updatePagination() {
    const start = totalItems > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0;
    const end   = Math.min(currentPage * itemsPerPage, totalItems);
    document.getElementById('currentRangeStart').textContent = start;
    document.getElementById('currentRangeEnd').textContent   = end;
    document.getElementById('totalItems').textContent        = totalItems;
    document.getElementById('btnPrevPage').disabled = currentPage === 1;
    document.getElementById('btnNextPage').disabled = currentPage >= totalPages;
}

function previousPage() { if (currentPage > 1)          { currentPage--; renderTickets(); window.scrollTo({top:0,behavior:'smooth'}); } }
function nextPage()      { if (currentPage < totalPages) { currentPage++; renderTickets(); window.scrollTo({top:0,behavior:'smooth'}); } }

// ==================== SCOPE (My / All) ====================
function getScopedTickets() {
    if (!IS_CUSTOMER_ADMIN || ticketScope === 'all') return allTickets;
    return allTickets.filter(t =>
        (t.submitted_by_email || '').toLowerCase() === USER_EMAIL.toLowerCase()
    );
}

// ==================== FILTERS ====================
function filterTickets(status) {
    currentFilter = status;

    const cardMap = {
        'all':                    'filterAll',
        'open':                   'filterOpen',
        'in process':             'filterInprocess',
        'waiting on customer':    'filterWaitingCustomer',
        'waiting on 3rd party':   'filterWaiting3rdParty',
        'waiting to confirmation':'filterWaitingConfirmation',
        'hold':                   'filterHold',
        'closed':                 'filterClosed',
    };

    Object.values(cardMap).forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('border-2', 'border-red-600', 'shadow-md');
        el.classList.add('border', 'border-gray-100');
    });

    const activeId = cardMap[status];
    if (activeId) {
        const el = document.getElementById(activeId);
        if (el) { el.classList.remove('border', 'border-gray-100'); el.classList.add('border-2', 'border-red-600', 'shadow-md'); }
    }

    applyColFilters();
}

function resetFilters() {
    colFilters = { ticketNumber: '', description: '', dateFrom: '', dateTo: '' };
    const ticketInput = document.getElementById('ticketFilterInput');
    if (ticketInput) ticketInput.value = '';
    const descInput = document.getElementById('descFilterInput');
    if (descInput) descInput.value = '';
    const dateFrom  = document.getElementById('dateFilterFrom');
    if (dateFrom)  dateFrom.value = '';
    const dateTo    = document.getElementById('dateFilterTo');
    if (dateTo)    dateTo.value = '';

    currentFilter = 'all';
    filterTickets('all');
}

// ==================== SORT ====================
function sortTickets(field) {
    if (sortField === field) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
        sortField = field;
        sortDir   = field === 'last_update' ? 'desc' : 'asc';
    }
    updateSortIcons();
    updateTicketSortButtons();
    applyColFilters();
}

function updateSortIcons() {
    ['last_update', 'ticket_number'].forEach(f => {
        const el = document.getElementById('sort-icon-' + f);
        if (!el) return;
        if (f === sortField) {
            el.textContent = sortDir === 'asc' ? '↑' : '↓';
            el.className   = 'text-red-500 font-bold';
        } else {
            el.textContent = '⇅';
            el.className   = 'text-gray-300';
        }
    });
}

function sortTicketsList(list) {
    return [...list].sort((a, b) => {
        let va, vb;
        if (sortField === 'last_update') {
            va = new Date(a.last_message_at || a.created_at);
            vb = new Date(b.last_message_at || b.created_at);
        } else {
            va = parseInt((a.ticket_number || '').replace(/\D/g, '')) || 0;
            vb = parseInt((b.ticket_number || '').replace(/\D/g, '')) || 0;
        }
        if (va < vb) return sortDir === 'asc' ? -1 :  1;
        if (va > vb) return sortDir === 'asc' ?  1 : -1;
        return 0;
    });
}

// ==================== COL FILTER PANELS ====================
function toggleColPanel(name, event) {
    if (event) event.stopPropagation();
    if (openPanel === name) { closeAllPanels(); return; }
    closeAllPanels();
    const panel = document.getElementById(name + 'Panel');
    if (!panel) return;

    // Position as fixed relative to the trigger button
    const btn  = event ? event.currentTarget : null;
    if (btn) {
        const rect = btn.getBoundingClientRect();
        panel.style.top  = (rect.bottom + 4) + 'px';
        panel.style.left = rect.left + 'px';
    }

    panel.classList.remove('hidden');

    // Clamp to viewport edges after render
    requestAnimationFrame(() => {
        const pr = panel.getBoundingClientRect();
        // Clamp right edge
        if (pr.right > window.innerWidth - 8) {
            panel.style.left = Math.max(8, window.innerWidth - pr.width - 8) + 'px';
        }
        // Clamp bottom edge: jika panel melebihi viewport, buka ke atas button
        if (btn && pr.bottom > window.innerHeight - 8) {
            const rect = btn.getBoundingClientRect();
            panel.style.top = Math.max(8, rect.top - pr.height - 4) + 'px';
        }
    });

    const caret = document.getElementById(name + 'Caret');
    if (caret) caret.style.transform = 'rotate(180deg)';
    openPanel = name;
}

function closeColPanel(name) {
    const panel = document.getElementById(name + 'Panel');
    if (panel) panel.classList.add('hidden');
    const caret = document.getElementById(name + 'Caret');
    if (caret) caret.style.transform = 'rotate(0deg)';
    if (openPanel === name) openPanel = null;
}

function closeAllPanels() {
    ['ticket', 'desc', 'date'].forEach(n => closeColPanel(n));
}

document.addEventListener('click', function() { closeAllPanels(); });
document.querySelector('.overflow-auto')?.addEventListener('scroll', function() { closeAllPanels(); }, { passive: true });

// ==================== COL FILTER LOGIC ====================
function applyColFilters() {
    let base = getScopedTickets();

    // Status card
    if (currentFilter !== 'all') {
        base = base.filter(t => t.status === currentFilter);
    }

    // Column: ticket number
    if (colFilters.ticketNumber) {
        const q = colFilters.ticketNumber.toLowerCase();
        base = base.filter(t => (t.ticket_number || '').toLowerCase().includes(q));
    }
    // Column: description
    if (colFilters.description) {
        const q = colFilters.description.toLowerCase();
        base = base.filter(t => (t.description || '').toLowerCase().includes(q));
    }
    // Column: date range
    if (colFilters.dateFrom) {
        base = base.filter(t => (t.created_at || '').slice(0, 10) >= colFilters.dateFrom);
    }
    if (colFilters.dateTo) {
        base = base.filter(t => (t.created_at || '').slice(0, 10) <= colFilters.dateTo);
    }
    filteredTickets = sortTicketsList(base);
    currentPage = 1;
    renderTickets();
    updateFilterIcons();
}

function setTicketSort(dir) {
    sortField = 'ticket_number';
    sortDir   = dir;
    updateSortIcons();
    updateTicketSortButtons();
    applyColFilters();
}

function updateTicketSortButtons() {
    const asc  = document.getElementById('ticketSortAsc');
    const desc = document.getElementById('ticketSortDesc');
    if (!asc || !desc) return;
    const isTicket = sortField === 'ticket_number';
    asc.classList.toggle('bg-red-700',    isTicket && sortDir === 'asc');
    asc.classList.toggle('text-white',    isTicket && sortDir === 'asc');
    asc.classList.toggle('border-red-700',isTicket && sortDir === 'asc');
    desc.classList.toggle('bg-red-700',    isTicket && sortDir === 'desc');
    desc.classList.toggle('text-white',    isTicket && sortDir === 'desc');
    desc.classList.toggle('border-red-700',isTicket && sortDir === 'desc');
}

function clearTicketFilter() {
    colFilters.ticketNumber = '';
    const el = document.getElementById('ticketFilterInput');
    if (el) el.value = '';
    sortField = 'last_update';
    sortDir   = 'desc';
    updateSortIcons();
    updateTicketSortButtons();
    applyColFilters();
}

function clearColFilter(field, inputId) {
    colFilters[field] = '';
    const el = document.getElementById(inputId);
    if (el) el.value = '';
    applyColFilters();
}

function applyDateColFilter() {
    const from = document.getElementById('dateFilterFrom').value;
    const to   = document.getElementById('dateFilterTo').value;
    const err  = document.getElementById('dateFilterError');
    if (from && to && to < from) { err.classList.remove('hidden'); return; }
    err.classList.add('hidden');
    colFilters.dateFrom = from;
    colFilters.dateTo   = to;
    closeColPanel('date');
    applyColFilters();
}

function clearDateColFilter() {
    colFilters.dateFrom = '';
    colFilters.dateTo   = '';
    document.getElementById('dateFilterFrom').value = '';
    document.getElementById('dateFilterTo').value   = '';
    document.getElementById('dateFilterError').classList.add('hidden');
    applyColFilters();
}


function updateFilterIcons() {
    const map = {
        ticket:   { icon: 'ticketFilterIcon',   active: !!colFilters.ticketNumber },
        desc:     { icon: 'descFilterIcon',     active: !!colFilters.description },
        date:     { icon: 'dateFilterIcon',     active: !!(colFilters.dateFrom || colFilters.dateTo) },
    };
    Object.values(map).forEach(({ icon, active }) => {
        const el = document.getElementById(icon);
        if (!el) return;
        el.classList.toggle('text-red-500', active);
        el.classList.toggle('text-gray-300', !active);
    });
    updateTicketSortButtons();
}

// ==================== COLLAPSIBLE ====================
const _sectionOpen = { statsSection: true };

function toggleSection(sectionId, chevronId) {
    const section = document.getElementById(sectionId);
    const chevron = document.getElementById(chevronId);
    if (!section) return;
    _sectionOpen[sectionId] = !_sectionOpen[sectionId];
    if (_sectionOpen[sectionId]) {
        section.style.maxHeight = section.scrollHeight + 'px';
        section.addEventListener('transitionend', function onEnd() {
            section.style.maxHeight = 'none';
            section.removeEventListener('transitionend', onEnd);
        }, { once: true });
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    } else {
        section.style.maxHeight = section.scrollHeight + 'px';
        requestAnimationFrame(() => requestAnimationFrame(() => { section.style.maxHeight = '0px'; }));
        if (chevron) chevron.style.transform = 'rotate(-90deg)';
    }
}

// ==================== UTILS ====================
function showLoading(show) {
    document.getElementById('loadingState').classList.toggle('hidden', !show);
    document.getElementById('ticketsContainer').classList.toggle('hidden', show);
    document.getElementById('emptyState').classList.add('hidden');
}

function showEmpty() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('ticketsContainer').classList.add('hidden');
    document.getElementById('emptyState').classList.remove('hidden');
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(text || ''));
    return d.innerHTML;
}

function relativeTime(date) {
    if (!(date instanceof Date)) date = new Date(date);
    const tz  = 'Asia/Jakarta';
    const now  = new Date();
    const toDay = d => new Date(d.toLocaleDateString('en-CA', { timeZone: tz }));
    const diffDays = Math.round((toDay(now) - toDay(date)) / 86400000);

    if (diffDays === 0) return date.toLocaleTimeString('id-ID', { timeZone: tz, hour: '2-digit', minute: '2-digit', hour12: false });
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7)   return date.toLocaleDateString('en-GB', { timeZone: tz, weekday: 'short' });
    if (date.getFullYear() === now.getFullYear()) return date.toLocaleDateString('en-GB', { timeZone: tz, day: '2-digit', month: 'short' });
    return date.toLocaleDateString('en-GB', { timeZone: tz, day: '2-digit', month: 'short', year: 'numeric' });
}
</script>
@endpush
