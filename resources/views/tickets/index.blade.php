@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Support Tickets')
@section('page-subtitle', 'Manage and track all support requests')

@section('header-actions')
<a href="{{ route('tickets.create') }}" class="flex items-center space-x-2 bg-red-800 hover:bg-red-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>New Ticket</span>
</a>
@endsection

@section('content')
@php
    $userRole = session('user.role.id', 3);
    $userId = session('user.id', 0);
    $isAdmin = $userRole === 1;
    $isCustomer = $userRole === 3;
@endphp

{{-- ─── STAGING TICKETS (Customer Only) ─────────────────────────── --}}
@if($isCustomer)
<div id="stagingSection" class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Submitted Tickets (Pending Validation)</h3>
            <span id="stagingBadge" class="hidden bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full"></span>
        </div>
        <button onclick="loadStagingTickets()" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
    </div>
    <div id="stagingList" class="space-y-2">
        <div class="text-center py-4 text-gray-400 text-sm" id="stagingLoading">Loading...</div>
    </div>
</div>
@endif

{{-- Status Filter Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
        $filters = [
            ['id' => 'all', 'label' => 'Total', 'count' => 'totalCount', 'active' => true],
            ['id' => 'in process', 'label' => 'In Process', 'count' => 'processCount', 'color' => 'blue'],
            ['id' => 'author action', 'label' => 'Author Action', 'count' => 'authorCount', 'color' => 'amber'],
            ['id' => 'proposed solution', 'label' => 'Proposed', 'count' => 'proposedCount', 'color' => 'purple'],
            ['id' => 'sent in to SAP', 'label' => 'Sent to SAP', 'count' => 'sapCount', 'color' => 'indigo'],
            ['id' => 'closed', 'label' => 'Closed', 'count' => 'closedCount', 'color' => 'green'],
        ];
    @endphp

    @foreach($filters as $filter)
    <div id="filter{{ ucfirst(str_replace(' ', '', $filter['id'])) }}" 
         class="stat-card bg-white rounded-xl p-4 {{ $filter['active'] ?? false ? 'border-2 border-red-600' : 'border border-gray-100' }} cursor-pointer hover:shadow-md transition-all" 
         onclick="filterTickets('{{ $filter['id'] }}')">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $filter['label'] }}</p>
        <p class="text-2xl font-bold text-{{ $filter['color'] ?? 'gray' }}-600 mt-1" id="{{ $filter['count'] }}">0</p>
    </div>
    @endforeach
</div>

{{-- Advanced Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="text-xs font-semibold text-gray-600 mb-2 block uppercase tracking-wide">Filter By</label>
            <select id="filterTypeSelect" onchange="updateFilterOptions()" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-800 bg-white">
                <option value="">Select Type</option>
                <option value="jarvies_status">Jarvies Status</option>
                <option value="status">Status</option>
                <option value="type">Type</option>
                <option value="priority">Priority</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-600 mb-2 block uppercase tracking-wide">Filter Value</label>
            <select id="filterValueSelect" disabled class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-800 bg-white disabled:bg-gray-50">
                <option value="">Select Type First</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-gray-600 mb-2 block uppercase tracking-wide">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <input type="text" id="searchInput" placeholder="Search by ID, description, customer..." class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-800 bg-white" onkeyup="searchTickets()">
            </div>
        </div>
    </div>
    <div class="flex gap-3 justify-end mt-5 pt-5 border-t border-gray-100">
        <button onclick="applyFilters()" class="inline-flex items-center gap-2 px-6 py-3 bg-red-800 text-white text-sm font-semibold rounded-xl hover:bg-red-900 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            Apply Filters
        </button>
        <button onclick="resetFilters()" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-700 text-sm font-semibold rounded-xl border border-gray-300 hover:bg-gray-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            Reset
        </button>
    </div>
</div>

{{-- Pagination Controls --}}
<div class="flex items-center justify-center mb-6">
    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg p-1.5 shadow-sm">
        <button onclick="previousPage()" id="btnPrevPage" disabled class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </button>
        <div class="px-4 py-1.5">
            <span class="text-sm font-medium text-gray-700">
                <span id="currentRangeStart">1</span>-<span id="currentRangeEnd">20</span>
            </span>
            <span class="text-sm text-gray-400 mx-1.5">of</span>
            <span class="text-sm font-medium text-gray-700" id="totalItems">0</span>
        </div>
        <button onclick="nextPage()" id="btnNextPage" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </button>
    </div>
</div>

{{-- Tickets List --}}
<div id="ticketsContainer" class="space-y-3">
    <div id="ticketsListBody"></div>
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

{{-- Ticket Detail Modal --}}
<div id="ticketDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-hidden">
    <div class="h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-6xl max-h-[90vh] flex flex-col shadow-2xl">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" id="modalTicketTitle">Ticket Details</h3>
                        <p class="text-sm text-gray-500" id="modalTicketId">#0</p>
                    </div>
                </div>
                <button onclick="closeTicketDetail()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 flex overflow-hidden">
                
                {{-- Messages Area --}}
                <div class="flex-1 flex flex-col border-r border-gray-200">
                    <div class="flex-1 overflow-y-auto p-6 space-y-4" id="ticketMessagesContainer">
                        {{-- Messages populated by JavaScript --}}
                    </div>
                    
                    {{-- Comment Input --}}
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex gap-3">
                            <input type="text" id="commentInput" placeholder="Add a comment..." 
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-800">
                            <button onclick="addComment()" 
                                    class="px-6 py-3 bg-red-800 text-white text-sm font-semibold rounded-xl hover:bg-red-900 transition-colors">
                                Send
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Properties Panel --}}
                <div class="w-80 bg-gray-50 p-6 overflow-y-auto">
                    <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wide">Properties</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Jarvies Status</label>
                            <select id="detailJarviesStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white {{ $isCustomer ? 'opacity-60' : '' }}" {{ $isCustomer ? 'disabled' : '' }}>
                                <option value="in process">In Process</option>
                                <option value="author action">Author Action</option>
                                <option value="proposed solution">Proposed Solution</option>
                                <option value="sent in to SAP">Sent to SAP</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Priority</label>
                            <select id="detailPriority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white {{ $isCustomer ? 'opacity-60' : '' }}" {{ $isCustomer ? 'disabled' : '' }}>
                                <option value="">—</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Ticket Type</label>
                            <input type="text" id="detailType" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white text-gray-700">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Customer</label>
                            <input type="text" id="detailCustomer" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Assigned To</label>
                            <input type="text" id="detailAgent" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Team Members</label>
                            <div id="detailMembers" class="min-h-9 px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600 flex flex-wrap gap-1.5"></div>
                        </div>
                        
                        <div>
                            <label class="text-xs font-semibold text-gray-600 mb-2 block">Created</label>
                            <input type="text" id="detailCreated" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        </div>
                        
                        @if($isAdmin)
                        <div class="pt-4 border-t border-gray-200">
                            <button onclick="updateTicket()" class="w-full px-4 py-3 bg-red-800 text-white text-sm font-semibold rounded-xl hover:bg-red-900 transition-colors">
                                Update Ticket
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.stat-card {
    transition: all 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
}
</style>
@endpush

@push('scripts')
<script>
// ==================== GLOBAL STATE ====================
let allTickets = [];
let filteredTickets = [];
let currentFilter = 'all';
let currentPage = 1;
let itemsPerPage = 20;
let totalItems = 0;
let totalPages = 0;
let currentTicketId = null;

const USER_ROLE = {{ $userRole }};
const USER_ID = {{ $userId }};
const IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};
const FETCH_URL = '{{ route("tickets.ajax.fetch") }}';
const CSRF_TOKEN = '{{ csrf_token() }}';

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Tickets page initialized', { USER_ROLE, USER_ID, IS_ADMIN });
    loadTickets();
    if (USER_ROLE === 3) loadStagingTickets();
    initializeEventListeners();

    // Auto-open ticket dari query param ?open=ID (dari showMyTicket redirect)
    const urlParams = new URLSearchParams(window.location.search);
    const openId = parseInt(urlParams.get('open'));
    if (openId) {
        // Langsung navigasi ke halaman detail tiket
        window.location.href = `/tickets/${openId}`;
    }
});

function initializeEventListeners() {
    // ESC key closes modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeTicketDetail();
    });
    
    // Modal backdrop click
    const modal = document.getElementById('ticketDetailModal');
    modal?.addEventListener('click', e => {
        if (e.target.id === 'ticketDetailModal') closeTicketDetail();
    });
}

// ==================== API FUNCTIONS ====================
async function loadTickets() {
    try {
        showLoading(true);
        
        const response = await fetch(FETCH_URL, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            credentials: 'same-origin'
        });
        
        if (response.status === 401) {
            window.location.href = '{{ route("login") }}';
            return;
        }
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data) {
            allTickets = data.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            filteredTickets = allTickets;
            updateStats();
            renderTickets();
        } else {
            showEmpty();
        }
    } catch (error) {
        console.error('Load tickets error:', error);
        showNotification('Failed to load tickets: ' + error.message, 'error');
        showEmpty();
    }
}

async function addComment() {
    const commentInput = document.getElementById('commentInput');
    const comment = commentInput.value.trim();
    
    if (!comment) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    if (!currentTicketId) {
        showNotification('No ticket selected', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/tickets/${currentTicketId}/comment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ comment })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Message sent successfully', 'success');
            commentInput.value = '';
            await loadTicketMessages(currentTicketId);
        } else {
            showNotification(data.message || 'Failed to send message', 'error');
        }
    } catch (error) {
        console.error('Add comment error:', error);
        showNotification('Failed to add comment', 'error');
    }
}

async function updateTicket() {
    if (!currentTicketId) return;
    
    const data = {
        jarvies_status: document.getElementById('detailJarviesStatus').value,
        ticket_priority: document.getElementById('detailPriority').value
    };
    
    try {
        const response = await fetch(`/tickets/${currentTicketId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Ticket updated successfully', 'success');
            closeTicketDetail();
            loadTickets(); // Reload tickets
        } else {
            showNotification(result.message || 'Failed to update ticket', 'error');
        }
    } catch (error) {
        console.error('Update ticket error:', error);
        showNotification('Failed to update ticket', 'error');
    }
}

// ==================== UI RENDERING ====================
function renderTickets() {
    const listBody = document.getElementById('ticketsListBody');
    showLoading(false);
    
    totalItems = filteredTickets.length;
    totalPages = Math.ceil(totalItems / itemsPerPage);

    if (totalItems === 0) {
        showEmpty();
        updatePagination();
        return;
    }

    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('ticketsContainer').classList.remove('hidden');

    const start = (currentPage - 1) * itemsPerPage;
    const end = Math.min(start + itemsPerPage, totalItems);
    const paginatedTickets = filteredTickets.slice(start, end);

    listBody.innerHTML = paginatedTickets.map(ticket => createTicketCard(ticket)).join('');
    updatePagination();
}

function createTicketCard(ticket) {
    const customerName = ticket.customer?.customer_name || ticket.customer?.company_name || 'Unknown';
    const agentName = ticket.employee?.employee_name || 'Unassigned';
    const createdDate = formatTimeAgo(new Date(ticket.created_at));

    const priorityColors = {
        'Low': 'bg-green-500',
        'Medium': 'bg-blue-500',
        'High': 'bg-red-500'
    };

    const statusColors = {
        'in process': 'bg-blue-100 text-blue-700',
        'author action': 'bg-amber-100 text-amber-700',
        'proposed solution': 'bg-purple-100 text-purple-700',
        'sent in to SAP': 'bg-indigo-100 text-indigo-700',
        'closed': 'bg-green-100 text-green-700'
    };

    const typeColors = {
        'Incident': 'bg-red-50 text-red-600',
        'Service Request': 'bg-indigo-50 text-indigo-600',
        'Change Request': 'bg-amber-50 text-amber-600',
        'Consult': 'bg-teal-50 text-teal-600'
    };

    const ticketTypeBadge = ticket.ticket_type
        ? `<span class="inline-flex px-2 py-0.5 rounded text-xs font-medium ${typeColors[ticket.ticket_type] || 'bg-gray-100 text-gray-600'}">${ticket.ticket_type}</span>`
        : '';

    const priorityLabel = ticket.ticket_priority || '—';
    const priorityDot = ticket.ticket_priority
        ? `<div class="w-2 h-2 rounded-full ${priorityColors[ticket.ticket_priority]}"></div>`
        : `<div class="w-2 h-2 rounded-full bg-gray-300"></div>`;

    return `
        <a href="/tickets/${ticket.ticket_id}" class="group block bg-white border border-gray-200 rounded-xl hover:border-gray-300 hover:shadow-md transition-all">
            <div class="flex items-start gap-4 p-4">
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(customerName)}&background=991b1b&color=fff&size=44&rounded=true"
                     alt="${customerName}" class="w-11 h-11 rounded-full flex-shrink-0">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold ${statusColors[ticket.jarvies_status] || 'bg-gray-100 text-gray-700'}">
                            ${ticket.jarvies_status || 'Open'}
                        </span>
                        ${ticketTypeBadge}
                    </div>

                    <h3 class="text-sm font-semibold text-gray-900 mb-1 group-hover:text-red-800 transition-colors">
                        ${ticket.description || 'No description'}
                        <span class="text-gray-400 font-normal">${ticket.ticket_number ? ticket.ticket_number : 'Pending'}</span>
                    </h3>

                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">${customerName}</span>
                        <span>•</span>
                        <span>${createdDate}</span>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                    <div class="flex items-center gap-1.5">
                        ${priorityDot}
                        <span class="text-xs font-medium text-gray-600">${priorityLabel}</span>
                    </div>

                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="truncate max-w-[100px]">${agentName}</span>
                    </div>
                </div>
            </div>
        </a>
    `;
}

function updateStats() {
    document.getElementById('totalCount').textContent = allTickets.length;
    document.getElementById('processCount').textContent = allTickets.filter(t => t.jarvies_status === 'in process').length;
    document.getElementById('authorCount').textContent = allTickets.filter(t => t.jarvies_status === 'author action').length;
    document.getElementById('proposedCount').textContent = allTickets.filter(t => t.jarvies_status === 'proposed solution').length;
    document.getElementById('sapCount').textContent = allTickets.filter(t => t.jarvies_status === 'sent in to SAP').length;
    document.getElementById('closedCount').textContent = allTickets.filter(t => t.jarvies_status === 'closed').length;
}

function updatePagination() {
    const start = totalItems > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0;
    const end = Math.min(currentPage * itemsPerPage, totalItems);
    
    document.getElementById('currentRangeStart').textContent = start;
    document.getElementById('currentRangeEnd').textContent = end;
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('btnPrevPage').disabled = currentPage === 1;
    document.getElementById('btnNextPage').disabled = currentPage >= totalPages;
}

// ==================== MODAL FUNCTIONS ====================
async function viewTicketDetail(ticketId) {
    const ticket = allTickets.find(t => t.ticket_id === ticketId);
    if (!ticket) return;

    currentTicketId = ticketId;

    document.getElementById('modalTicketTitle').textContent = ticket.description || 'Ticket Details';
    document.getElementById('modalTicketId').textContent = ticket.ticket_number || 'Pending';

    const customerName = ticket.customer?.customer_name || ticket.customer?.company_name || 'Customer';
    const employeeName = ticket.employee?.employee_name || 'Unassigned';

    document.getElementById('detailJarviesStatus').value = ticket.jarvies_status || 'in process';
    document.getElementById('detailPriority').value = ticket.ticket_priority || '';
    document.getElementById('detailType').value = ticket.ticket_type || '—';
    document.getElementById('detailCustomer').value = customerName;
    document.getElementById('detailAgent').value = employeeName;
    document.getElementById('detailCreated').value = new Date(ticket.created_at).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });

    // Team Members
    const membersEl = document.getElementById('detailMembers');
    if (ticket.members && ticket.members.length > 0) {
        membersEl.innerHTML = ticket.members.map(m =>
            `<span class="inline-flex px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium">${escapeHtml(m.employee_name)}</span>`
        ).join('');
    } else {
        membersEl.innerHTML = '<span class="text-gray-400 text-xs">None</span>';
    }

    document.getElementById('ticketDetailModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Fetch & render messages dari server
    await loadTicketMessages(ticketId);
}

async function loadTicketMessages(ticketId) {
    const container = document.getElementById('ticketMessagesContainer');
    container.innerHTML = `
        <div class="flex items-center justify-center py-10 text-gray-400 text-sm">
            <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Loading messages...
        </div>`;

    try {
        const response = await fetch(`/tickets/${ticketId}/messages`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(msg => renderMessage(msg)).join('');
            setupEmailFrames();
            container.scrollTop = container.scrollHeight;
        } else {
            container.innerHTML = `
                <div class="text-center py-10 text-gray-400 text-sm">
                    <p>No messages yet. Be the first to reply.</p>
                </div>`;
        }
    } catch (err) {
        console.error('loadTicketMessages error:', err);
        container.innerHTML = `<p class="text-center py-6 text-red-400 text-sm">Failed to load messages.</p>`;
    }
}

function renderMessage(msg) {
    const isCustomer  = msg.sender_type === 'customer';
    const isSystem    = msg.sender_type === 'system';
    const initials    = (msg.sender_name || '?').substring(0, 1).toUpperCase();
    const timeAgo     = formatTimeAgo(new Date(msg.created_at));
    const isEmail     = msg.channel === 'email';

    const channelBadge = isEmail
        ? `<span class="text-xs text-gray-400 ml-1 inline-flex items-center gap-0.5"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>email</span>`
        : '';

    if (isSystem) {
        return `
            <div class="flex justify-center">
                <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                    ${escapeHtml(msg.message)}
                </span>
            </div>`;
    }

    // Tentukan konten pesan: email channel → iframe HTML, web → plain/Quill HTML
    let messageBody;
    if (isEmail && msg.message_html) {
        messageBody = `<iframe class="email-frame" data-srcdoc="${encodeURIComponent(msg.message_html)}"
            sandbox="allow-same-origin"
            style="width:100%;min-height:120px;border:none;display:block;"
            scrolling="no"></iframe>`;
    } else {
        messageBody = `<p class="text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(msg.message || '')}</p>`;
    }

    // CC emails
    const ccLine = msg.cc_emails
        ? `<div class="text-xs text-gray-400 mt-1.5">CC: ${escapeHtml(msg.cc_emails)}</div>`
        : '';

    // Attachments (non-inline)
    const attachmentsHtml = (msg.attachments && msg.attachments.length > 0)
        ? `<div class="flex flex-wrap gap-2 mt-2">
            ${msg.attachments.map(a => `
                <a href="${escapeHtml(a.url || '#')}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-gray-200 rounded-lg text-xs text-gray-600 hover:text-red-800 hover:border-red-300 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    ${escapeHtml(a.file_name)}
                </a>`).join('')}
           </div>`
        : '';

    // Customer (saya) → kanan (flex-row-reverse), Employee/support → kiri
    const avatarColor = isCustomer ? 'bg-red-600' : 'bg-blue-600';
    return `
        <div class="flex gap-3 ${isCustomer ? 'flex-row-reverse' : ''}">
            <div class="w-9 h-9 ${avatarColor} rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-white">${initials}</span>
            </div>
            <div class="flex-1 ${isCustomer ? 'flex flex-col items-end' : ''} min-w-0">
                <div class="flex items-center gap-2 mb-1 ${isCustomer ? 'justify-end' : ''}">
                    <span class="font-semibold text-sm text-gray-900">${escapeHtml(msg.sender_name || 'Unknown')}</span>
                    ${channelBadge}
                    <span class="text-xs text-gray-400">${timeAgo}</span>
                </div>
                <div class="${isCustomer ? 'bg-red-50' : 'bg-gray-100'} rounded-xl px-4 py-3 max-w-full overflow-hidden">
                    ${messageBody}
                    ${ccLine}
                    ${attachmentsHtml}
                </div>
            </div>
        </div>`;
}

function setupEmailFrames() {
    document.querySelectorAll('iframe.email-frame').forEach(frame => {
        if (frame.dataset.initialized) return;
        frame.dataset.initialized = '1';
        const html = decodeURIComponent(frame.dataset.srcdoc || '');
        frame.srcdoc = html;
        frame.addEventListener('load', () => {
            try {
                const h = frame.contentDocument?.documentElement?.scrollHeight;
                if (h) frame.style.minHeight = Math.min(h + 20, 600) + 'px';
            } catch {}
        }, { once: true });
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text || ''));
    return div.innerHTML;
}

function closeTicketDetail() {
    document.getElementById('ticketDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentTicketId = null;
}

// ==================== FILTER FUNCTIONS ====================
function filterTickets(status) {
    currentFilter = status;
    
    document.querySelectorAll('[id^="filter"]').forEach(el => {
        el.classList.remove('border-red-600', 'border-2');
        el.classList.add('border', 'border-gray-100');
    });

    const filterMap = {
        'all': 'filterAll',
        'in process': 'filterInprocess',
        'author action': 'filterAuthorAction',
        'proposed solution': 'filterProposedSolution',
        'sent in to SAP': 'filterSentInToSAP',
        'closed': 'filterClosed'
    };

    const filterId = filterMap[status];
    if (filterId) {
        const el = document.getElementById(filterId);
        if (el) {
            el.classList.remove('border', 'border-gray-100');
            el.classList.add('border-2', 'border-red-600');
        }
    }

    filteredTickets = status === 'all' 
        ? allTickets 
        : allTickets.filter(t => t.jarvies_status === status);
    
    currentPage = 1;
    renderTickets();
}

function updateFilterOptions() {
    const filterType = document.getElementById('filterTypeSelect').value;
    const filterValue = document.getElementById('filterValueSelect');
    
    filterValue.disabled = !filterType;
    filterValue.innerHTML = '<option value="">Select value</option>';

    const options = {
        'jarvies_status': ['in process', 'author action', 'proposed solution', 'closed', 'sent in to SAP'],
        'status': ['open', 'in_progress', 'hold', 'cancel', 'closed', 'reply'],
        'type': ['AMS', 'MO', 'ATS', 'Project', 'Internal'],
        'priority': ['Low', 'Medium', 'High']
    };

    if (filterType && options[filterType]) {
        options[filterType].forEach(opt => {
            filterValue.innerHTML += `<option value="${opt}">${opt}</option>`;
        });
    }
}

function applyFilters() {
    const filterType = document.getElementById('filterTypeSelect').value;
    const filterValue = document.getElementById('filterValueSelect').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    filteredTickets = allTickets.filter(ticket => {
        const matchesSearch = !searchTerm || 
            String(ticket.ticket_id).includes(searchTerm) ||
            (ticket.description || '').toLowerCase().includes(searchTerm) ||
            (ticket.customer?.customer_name || '').toLowerCase().includes(searchTerm) ||
            (ticket.customer?.company_name || '').toLowerCase().includes(searchTerm);

        const matchesFilter = !filterType || !filterValue || ticket[filterType] === filterValue;
        const matchesStatus = currentFilter === 'all' || ticket.jarvies_status === currentFilter;

        return matchesSearch && matchesFilter && matchesStatus;
    });

    currentPage = 1;
    renderTickets();
}

function searchTickets() {
    applyFilters();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterTypeSelect').value = '';
    document.getElementById('filterValueSelect').value = '';
    document.getElementById('filterValueSelect').disabled = true;
    currentFilter = 'all';
    filterTickets('all');
}

// ==================== PAGINATION ====================
function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        renderTickets();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        renderTickets();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// ==================== UTILITY FUNCTIONS ====================
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

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-xl z-[60] font-medium transition-opacity`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function formatTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// ==================== STAGING TICKETS (Customer Only) ====================
async function loadStagingTickets() {
    if (USER_ROLE !== 3) return;

    const list    = document.getElementById('stagingList');
    const badge   = document.getElementById('stagingBadge');
    const loading = document.getElementById('stagingLoading');

    if (loading) loading.classList.remove('hidden');

    try {
        const res  = await fetch('{{ route("tickets.staging") }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (loading) loading.remove();

        if (!json.success || !json.data.length) {
            list.innerHTML = '<div class="text-center py-3 text-gray-400 text-sm bg-white rounded-xl border border-gray-100">Belum ada tiket yang menunggu validasi.</div>';
            return;
        }

        const pendingCount = json.data.filter(s => s.status === 'unvalidated').length;
        if (pendingCount > 0) {
            badge.textContent = pendingCount + ' menunggu';
            badge.classList.remove('hidden');
        }

        list.innerHTML = json.data.map(s => {
            const statusConfig = {
                unvalidated: { bg: 'bg-yellow-50 border-yellow-200', badge: 'bg-yellow-100 text-yellow-700', label: 'Menunggu Validasi', icon: '⏳' },
                approved:    { bg: 'bg-green-50 border-green-200',  badge: 'bg-green-100 text-green-700',  label: 'Disetujui', icon: '✅' },
                rejected:    { bg: 'bg-red-50 border-red-200',      badge: 'bg-red-100 text-red-700',      label: 'Ditolak', icon: '❌' },
            };
            const cfg  = statusConfig[s.status] ?? statusConfig.unvalidated;
            const date = s.created_at ? new Date(s.created_at).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : '-';

            const ticketLink = s.status === 'approved' && s.ticket_id
                ? `<a href="/tickets/${s.ticket_id}" class="text-xs font-semibold text-red-700 hover:underline ml-2">Lihat Tiket #${s.ticket_number ?? s.ticket_id} →</a>`
                : '';

            const rejectionNote = s.status === 'rejected' && s.rejection_reason
                ? `<p class="text-xs text-red-600 mt-1"><span class="font-semibold">Alasan:</span> ${s.rejection_reason}</p>`
                : '';

            return `
            <div class="flex items-start gap-3 px-4 py-3 rounded-xl border ${cfg.bg} text-sm">
                <span class="text-base mt-0.5">${cfg.icon}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-gray-800 truncate">${s.description.length > 70 ? s.description.substring(0,70)+'…' : s.description}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold ${cfg.badge}">${cfg.label}</span>
                        ${s.ticket_priority ? `<span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">${s.ticket_priority}</span>` : ''}
                        ${ticketLink}
                    </div>
                    ${rejectionNote}
                    <p class="text-xs text-gray-400 mt-0.5">Dikirim: ${date}</p>
                </div>
            </div>`;
        }).join('');

    } catch (err) {
        console.error('loadStagingTickets error', err);
        if (loading) loading.remove();
        list.innerHTML = '<div class="text-xs text-gray-400 text-center py-2">Gagal memuat data staging.</div>';
    }
}
</script>
@endpush