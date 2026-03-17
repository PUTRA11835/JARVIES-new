@extends('layouts.app')

@section('title', 'New Ticket')
@section('page-title', 'Support Tickets')
@section('page-subtitle', 'Submit a new support request')

@section('header-actions')
<a href="{{ route('tickets.index') }}" class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    <span>Back to Tickets</span>
</a>
@endsection

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">New Support Ticket</h2>
            <p class="text-sm text-gray-400 mt-0.5">Our support team will review and respond to your request.</p>
        </div>

        {{-- Email Notification Row (optional) --}}
        <div id="emailNotifRow" class="px-6 py-3 bg-gray-50 border-b border-gray-100">
            <div id="emailNotifLoading" class="flex items-center gap-2 text-xs text-gray-400 animate-pulse">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                </svg>
                Checking email settings...
            </div>

            {{-- Linked state --}}
            <div id="emailLinkedRow" class="hidden items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs text-green-700">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Replies will be sent to <span id="linkedEmailAddr" class="font-semibold"></span></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="openOAuthModal()" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Change</button>
                    <span class="text-gray-300">·</span>
                    <button type="button" onclick="disconnectEmail()" class="text-xs text-red-400 hover:text-red-600 transition-colors">Disconnect</button>
                </div>
            </div>

            {{-- Not linked state --}}
            <div id="emailNotLinkedRow" class="hidden items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Connect your email to receive replies in your inbox <span class="text-gray-400">(optional)</span></span>
                </div>
                <button type="button" onclick="openOAuthModal()"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors shrink-0">
                    Link email →
                </button>
            </div>
        </div>

        {{-- Form --}}
        <form id="ticketForm" class="px-6 py-6 space-y-5">
            @csrf

            {{-- Subject --}}
            <div>
                <label for="subject" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Subject <span class="text-red-500">*</span>
                </label>
                <input type="text" id="subject" name="subject"
                    placeholder="Brief description of your issue..."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all"
                    required>
            </div>

            {{-- Name & No HP --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Name</label>
                    <input type="text" id="name" name="name"
                        placeholder="Contact person name..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                </div>
                <div>
                    <label for="no_hp" class="block text-sm font-semibold text-gray-700 mb-1.5">No HP</label>
                    <input type="text" id="no_hp" name="no_hp"
                        placeholder="Phone number..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                </div>
            </div>

            {{-- Module & Client --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="module" class="block text-sm font-semibold text-gray-700 mb-1.5">Module</label>
                    <input type="text" id="module" name="module"
                        placeholder="Related module..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                </div>
                <div>
                    <label for="client" class="block text-sm font-semibold text-gray-700 mb-1.5">Client</label>
                    <input type="text" id="client" name="client"
                        placeholder="Client name..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                </div>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                <div class="flex items-center gap-2 flex-wrap">
                    @foreach(['Very High', 'High', 'Medium', 'Low'] as $p)
                    @php
                        $colors = [
                            'Very High' => 'peer-checked:bg-red-100 peer-checked:border-red-500 peer-checked:text-red-700',
                            'High'      => 'peer-checked:bg-orange-100 peer-checked:border-orange-400 peer-checked:text-orange-700',
                            'Medium'    => 'peer-checked:bg-blue-100 peer-checked:border-blue-400 peer-checked:text-blue-700',
                            'Low'       => 'peer-checked:bg-green-100 peer-checked:border-green-400 peer-checked:text-green-700',
                        ];
                    @endphp
                    <label class="relative cursor-pointer">
                        <input type="radio" name="ticket_priority" value="{{ $p }}"
                            class="peer sr-only"
                            {{ $p === 'Medium' ? 'checked' : '' }}>
                        <span class="inline-flex items-center px-4 py-1.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-500 bg-white transition-all
                            {{ $colors[$p] }} hover:bg-gray-50">
                            {{ $p }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- CC (always shown, applies when OAuth email is active) --}}
            <div id="ccFieldWrapper">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    CC
                    <span class="text-xs font-normal text-gray-400 ml-1">(optional — copy recipients for ticket emails)</span>
                </label>
                {{-- Tag input: press Enter/comma/space to add an email --}}
                <div id="ccTagsContainer"
                    class="flex flex-wrap gap-1.5 px-3 py-2 border border-gray-300 rounded-xl text-sm cursor-text min-h-[42px] items-center focus-within:ring-2 focus-within:ring-red-800 focus-within:border-transparent transition-all">
                    <input type="text" id="ccInput"
                        placeholder="Add email then press Enter…"
                        autocomplete="off"
                        class="outline-none flex-1 min-w-[200px] text-sm text-gray-800 placeholder-gray-400 bg-transparent py-0.5">
                </div>
                {{-- Hidden inputs created by JS --}}
                <div id="ccHiddenInputs"></div>
                <p id="ccError" class="text-xs text-red-600 mt-1 hidden"></p>
            </div>

            {{-- Body --}}
            <div>
                <label for="body" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Details <span class="text-red-500">*</span>
                </label>
                <textarea id="body" name="body" rows="8"
                    placeholder="Describe your issue in detail. Include steps to reproduce, error messages, or screenshots if relevant..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all resize-none leading-relaxed"
                    required></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-2">
                <button type="submit" id="sendBtn"
                    class="inline-flex items-center gap-2 bg-red-800 hover:bg-red-900 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg id="sendIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg id="sendSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="sendText">Submit Ticket</span>
                </button>

                <button type="button" onclick="discardTicket()"
                    class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                    Discard
                </button>
            </div>
        </form>

    </div>
</div>

{{-- =================== OAUTH PROVIDER MODAL =================== --}}
<div id="oauthModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">

        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-gray-800">Link Your Email Account</h3>
            <button onclick="closeOAuthModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p class="text-sm text-gray-500 mb-4">
            Connect your email so ticket replies are sent directly to your inbox.
        </p>

        {{-- Google --}}
        <a href="{{ route('oauth.email.redirect', ['provider' => 'google', 'return' => route('tickets.create')]) }}"
            class="flex items-center gap-3 w-full px-4 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors mb-3 group">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <div class="flex-1 text-left">
                <div class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Continue with Google</div>
                <div class="text-xs text-gray-400">Gmail & Google Account</div>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- Microsoft --}}
        <a href="{{ route('oauth.email.redirect', ['provider' => 'azure', 'return' => route('tickets.create')]) }}"
            class="flex items-center gap-3 w-full px-4 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors mb-4 group">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                <path d="M11.4 2H2v9.4h9.4V2z" fill="#F25022"/>
                <path d="M22 2h-9.4v9.4H22V2z" fill="#7FBA00"/>
                <path d="M11.4 12.6H2V22h9.4v-9.4z" fill="#00A4EF"/>
                <path d="M22 12.6h-9.4V22H22v-9.4z" fill="#FFB900"/>
            </svg>
            <div class="flex-1 text-left">
                <div class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Continue with Microsoft</div>
                <div class="text-xs text-gray-400">Outlook & Microsoft Account</div>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <p class="text-xs text-gray-400 text-center">
            One-time setup. Your token is stored securely and only used for sending ticket notifications.
        </p>
    </div>
</div>

{{-- =================== ALERT MODAL =================== --}}
<div id="alertModal" class="hidden fixed inset-0 bg-black/50 z-60 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div id="alertIcon" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h3 id="alertTitle" class="text-base font-semibold text-gray-900 mb-2"></h3>
        <p id="alertMessage" class="text-sm text-gray-500 mb-5"></p>
        <button onclick="closeAlertModal()"
            class="w-full py-2.5 bg-red-800 hover:bg-red-900 text-white text-sm font-semibold rounded-xl transition-colors">
            OK
        </button>
    </div>
</div>

{{-- =================== CONFIRM MODAL =================== --}}
<div id="confirmModal" class="hidden fixed inset-0 bg-black/50 z-60 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 id="confirmTitle" class="text-base font-semibold text-gray-900 mb-2"></h3>
        <p id="confirmMessage" class="text-sm text-gray-500 mb-5"></p>
        <div class="flex gap-3">
            <button id="confirmCancelBtn"
                class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button id="confirmOkBtn"
                class="flex-1 py-2.5 bg-red-800 hover:bg-red-900 text-white text-sm font-semibold rounded-xl transition-colors">
                Yes, Continue
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

// ===== Alert Modal =====
const _alertConfigs = {
    success: {
        bg: 'bg-green-100',
        icon: `<svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`,
        title: 'Success',
    },
    error: {
        bg: 'bg-red-100',
        icon: `<svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`,
        title: 'An Error Occurred',
    },
    info: {
        bg: 'bg-blue-100',
        icon: `<svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        title: 'Information',
    },
    warning: {
        bg: 'bg-amber-100',
        icon: `<svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
        title: 'Warning',
    },
};

let _alertCallback = null;

function showAlert(message, type = 'info', title = null, onClose = null) {
    const cfg = _alertConfigs[type] || _alertConfigs.info;
    _alertCallback = onClose;
    const iconEl = document.getElementById('alertIcon');
    iconEl.className = `w-14 h-14 rounded-full ${cfg.bg} flex items-center justify-center mx-auto mb-4`;
    iconEl.innerHTML = cfg.icon;
    document.getElementById('alertTitle').textContent   = title || cfg.title;
    document.getElementById('alertMessage').textContent = message;
    const modal = document.getElementById('alertModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAlertModal() {
    const modal = document.getElementById('alertModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    if (typeof _alertCallback === 'function') {
        const cb = _alertCallback;
        _alertCallback = null;
        cb();
    }
}

// ===== Confirm Modal =====
let _confirmResolve = null;

function showConfirm(message, title = 'Confirm') {
    return new Promise(resolve => {
        _confirmResolve = resolve;
        document.getElementById('confirmTitle').textContent   = title;
        document.getElementById('confirmMessage').textContent = message;
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
}

function resolveConfirm(value) {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    if (_confirmResolve) {
        const r = _confirmResolve;
        _confirmResolve = null;
        r(value);
    }
}

document.getElementById('confirmCancelBtn').addEventListener('click', () => resolveConfirm(false));
document.getElementById('confirmOkBtn').addEventListener('click',     () => resolveConfirm(true));

// ===== OAuth Email Status =====
let emailLinked = false;

async function loadEmailStatus() {
    try {
        const res  = await fetch('{{ route("oauth.email.status") }}');
        const data = await res.json();

        document.getElementById('emailNotifLoading').classList.add('hidden');

        if (data.linked && !data.expired) {
            emailLinked = true;

            const row = document.getElementById('emailLinkedRow');
            document.getElementById('linkedEmailAddr').textContent = data.email;
            row.classList.remove('hidden');
            row.classList.add('flex');
        } else {
            emailLinked = false;

            const row = document.getElementById('emailNotLinkedRow');
            row.classList.remove('hidden');
            row.classList.add('flex');
        }
    } catch (err) {
        // Check failed — hide loading, form remains usable
        document.getElementById('emailNotifLoading').classList.add('hidden');
    }
}

function openOAuthModal() {
    const m = document.getElementById('oauthModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeOAuthModal() {
    const m = document.getElementById('oauthModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.getElementById('oauthModal').addEventListener('click', function (e) {
    if (e.target === this) closeOAuthModal();
});

async function disconnectEmail() {
    const ok = await showConfirm('The connected email account will be disconnected.', 'Disconnect email account?');
    if (!ok) return;

    const res  = await fetch('{{ route("oauth.email.disconnect") }}', {
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
        emailLinked = false;
        document.getElementById('emailLinkedRow').classList.add('hidden');
        document.getElementById('emailLinkedRow').classList.remove('flex');
        const row = document.getElementById('emailNotLinkedRow');
        row.classList.remove('hidden');
        row.classList.add('flex');
        showAlert('Email account successfully disconnected.', 'info');
    }
}

async function discardTicket() {
    const ok = await showConfirm('Unsaved changes will be lost.', 'Discard this ticket?');
    if (ok) window.location.href = '{{ route("tickets.index") }}';
}

// ===== CC Tag Input =====
const ccTags  = [];
const ccInput = document.getElementById('ccInput');
const ccContainer = document.getElementById('ccTagsContainer');

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function addCcTag(email) {
    email = email.trim().toLowerCase();
    if (!email) return;
    if (!isValidEmail(email)) {
        const err = document.getElementById('ccError');
        err.textContent = `"${email}" is not a valid email address.`;
        err.classList.remove('hidden');
        return;
    }
    if (ccTags.includes(email)) {
        ccInput.value = '';
        return;
    }
    if (ccTags.length >= 10) {
        const err = document.getElementById('ccError');
        err.textContent = 'Maximum 10 CC email addresses.';
        err.classList.remove('hidden');
        return;
    }
    document.getElementById('ccError').classList.add('hidden');
    ccTags.push(email);

    // Create tag chip
    const chip = document.createElement('span');
    chip.className = 'inline-flex items-center gap-1 bg-red-50 border border-red-200 text-red-800 text-xs font-medium px-2 py-0.5 rounded-full';
    chip.dataset.email = email;
    chip.innerHTML = `${escHtmlCreate(email)}<button type="button" class="ml-0.5 hover:text-red-600 transition-colors" onclick="removeCcTag('${escHtmlCreate(email)}')">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>`;
    ccContainer.insertBefore(chip, ccInput);

    // Hidden input for form submit
    const hidden = document.createElement('input');
    hidden.type  = 'hidden';
    hidden.name  = 'cc_tag_' + ccTags.length;
    hidden.value = email;
    hidden.dataset.ccTag = email;
    document.getElementById('ccHiddenInputs').appendChild(hidden);

    ccInput.value = '';
}

function removeCcTag(email) {
    const idx = ccTags.indexOf(email);
    if (idx > -1) ccTags.splice(idx, 1);

    // Remove chip
    ccContainer.querySelectorAll('[data-email]').forEach(chip => {
        if (chip.dataset.email === email) chip.remove();
    });
    // Remove hidden input
    document.getElementById('ccHiddenInputs').querySelectorAll('[data-cc-tag]').forEach(inp => {
        if (inp.dataset.ccTag === email) inp.remove();
    });
}

function escHtmlCreate(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

ccInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
        e.preventDefault();
        addCcTag(this.value);
    } else if (e.key === 'Backspace' && this.value === '' && ccTags.length > 0) {
        removeCcTag(ccTags[ccTags.length - 1]);
    }
});

ccInput.addEventListener('blur', function () {
    if (this.value.trim()) addCcTag(this.value);
});

ccContainer.addEventListener('click', () => ccInput.focus());

// ===== Form Submit =====
document.getElementById('ticketForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const subject  = document.getElementById('subject').value.trim();
    const body     = document.getElementById('body').value.trim();
    const priority = document.querySelector('input[name="ticket_priority"]:checked')?.value || 'Medium';
    const name     = document.getElementById('name').value.trim() || undefined;
    const no_hp    = document.getElementById('no_hp').value.trim() || undefined;
    const module   = document.getElementById('module').value.trim() || undefined;
    const client   = document.getElementById('client').value.trim() || undefined;

    // Flush any email being typed but not yet confirmed
    if (ccInput.value.trim()) addCcTag(ccInput.value);

    if (!subject) { showAlert('Subject cannot be empty.', 'warning'); return; }
    if (!body)    { showAlert('Details cannot be empty.', 'warning'); return; }

    setLoading(true);

    try {
        const res  = await fetch('{{ route("tickets.store") }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                description:     subject,
                body:            body,
                ticket_priority: priority,
                cc_emails:       ccTags.length > 0 ? ccTags : undefined,
                name,
                no_hp,
                module,
                client,
            }),
        });

        const data = await res.json();

        if (data.success) {
            let msg, title;
            if (data.staging) {
                title = 'Ticket Submitted';
                msg   = data.email_sent
                    ? 'Your ticket is awaiting admin validation. A notification email has been sent to your inbox.'
                    : 'Your ticket is awaiting admin validation.';
            } else {
                title = 'Ticket Created';
                msg   = 'Ticket created successfully.';
            }
            showAlert(msg, 'success', title, () => {
                window.location.href = '{{ route("tickets.index") }}';
            });
        } else {
            showAlert(data.message || 'Failed to submit ticket. Please try again.', 'error');
            setLoading(false);
        }
    } catch (err) {
        console.error(err);
        showAlert('A network error occurred. Please check your connection and try again.', 'error');
        setLoading(false);
    }
});

function setLoading(loading) {
    const btn = document.getElementById('sendBtn');
    btn.disabled = loading;
    document.getElementById('sendIcon').classList.toggle('hidden', loading);
    document.getElementById('sendSpinner').classList.toggle('hidden', !loading);
    document.getElementById('sendText').textContent = loading ? 'Submitting...' : 'Submit Ticket';
}

// ===== Flash session from OAuth callback =====
@if(session('oauth_success'))
document.addEventListener('DOMContentLoaded', () => showAlert(@json(session('oauth_success')), 'success', 'Email Connected'));
@endif
@if(session('oauth_error'))
document.addEventListener('DOMContentLoaded', () => showAlert(@json(session('oauth_error')), 'error', 'Connection Failed'));
@endif

loadEmailStatus();
</script>
@endpush
