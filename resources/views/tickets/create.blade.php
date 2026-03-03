@extends('layouts.app')

@section('title', 'New Ticket')
@section('page-title', 'Support Tickets')
@section('page-subtitle', 'Compose and submit a new support request')

@section('header-actions')
<a href="{{ route('tickets.index') }}" class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    <span>Back to Tickets</span>
</a>
@endsection

@section('content')

<div class="max-w-3xl mx-auto">

    {{-- Email Compose Card --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden flex flex-col" style="min-height: 580px;">

        {{-- Compose Header --}}
        <div class="flex items-center justify-between px-5 py-3.5 bg-gray-700 rounded-t-2xl">
            <h2 class="text-sm font-semibold text-white tracking-wide">New Ticket</h2>
            <a href="{{ route('tickets.index') }}" class="text-gray-300 hover:text-white transition-colors p-1 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>

        <form id="composeForm" class="flex flex-col flex-1">
            @csrf

            {{-- From (linked sender email) --}}
            <div class="flex items-center px-5 py-3 border-b border-gray-100 gap-3">
                <span class="text-sm text-gray-400 w-14 shrink-0">From</span>
                <div id="fromStatus" class="flex-1 flex items-center gap-2">
                    <div class="inline-flex items-center gap-2 text-xs text-gray-400 animate-pulse">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        </svg>
                        Checking linked account...
                    </div>
                </div>
                <button type="button" id="linkEmailBtn"
                    onclick="openOAuthModal()"
                    class="hidden text-xs text-blue-600 hover:text-blue-800 underline shrink-0">
                    Link email
                </button>
                <button type="button" id="changeEmailBtn"
                    onclick="openOAuthModal()"
                    class="hidden text-xs text-gray-400 hover:text-gray-600 shrink-0">
                    Change
                </button>
                <button type="button" id="disconnectBtn"
                    onclick="disconnectEmail()"
                    class="hidden text-xs text-red-400 hover:text-red-600 shrink-0">
                    Disconnect
                </button>
            </div>

            {{-- To --}}
            <div class="flex items-center px-5 py-3 border-b border-gray-100">
                <span class="text-sm text-gray-400 w-14 shrink-0">To</span>
                <div class="flex-1 flex items-center gap-2">
                    <div class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 text-sm px-3 py-1 rounded-full">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-medium">{{ env('MS_SENDER_EMAIL', 'support@eclecticonsulting.onmicrosoft.com') }}</span>
                    </div>
                </div>
                {{-- Priority pill --}}
                <div class="flex items-center gap-2 ml-3">
                    <label class="text-xs text-gray-400 shrink-0">Priority</label>
                    <select id="ticket_priority" name="ticket_priority"
                        class="text-xs font-semibold border border-gray-200 rounded-full px-3 py-1 bg-white focus:outline-none focus:ring-2 focus:ring-red-800 cursor-pointer">
                        <option value="Low">🟢 Low</option>
                        <option value="Medium" selected>🔵 Medium</option>
                        <option value="High">🔴 High</option>
                    </select>
                </div>
            </div>

            {{-- Subject --}}
            <div class="flex items-center px-5 py-3 border-b border-gray-100">
                <span class="text-sm text-gray-400 w-14 shrink-0">Subject</span>
                <input type="text" id="subject" name="subject"
                    placeholder="Brief description of your issue..."
                    class="flex-1 text-sm text-gray-900 placeholder-gray-400 focus:outline-none bg-transparent"
                    required>
            </div>

            {{-- Body --}}
            <div class="flex-1 px-5 pt-4 pb-2">
                <textarea id="body" name="body"
                    placeholder="Describe your issue in detail..."
                    class="w-full h-full min-h-[220px] text-sm text-gray-800 placeholder-gray-400 focus:outline-none bg-transparent resize-none leading-relaxed"
                    required></textarea>
            </div>

            {{-- Bottom Toolbar --}}
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-b-2xl">

                {{-- Send Button --}}
                <button type="submit" id="sendBtn"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-full transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-blue-600">
                    <svg id="sendIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <svg id="sendSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="sendText">Send</span>
                </button>

                {{-- Toolbar Icons --}}
                <div class="flex items-center gap-1 text-gray-400">
                    <button type="button" title="Formatting" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/>
                        </svg>
                    </button>
                    <button type="button" title="Attach file" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                    </button>
                    <button type="button" title="Insert link" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </button>
                    <button type="button" title="Emoji" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <div class="w-px h-4 bg-gray-300 mx-1"></div>
                    <button type="button" onclick="discardTicket()"
                        title="Discard" class="p-2 rounded-full hover:bg-red-100 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

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
            Hubungkan email Anda agar tiket terkirim langsung dari inbox Anda ke tim support kami.
        </p>

        {{-- Google --}}
        <a href="{{ route('oauth.email.redirect', 'google') }}"
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
        <a href="{{ route('oauth.email.redirect', 'azure') }}"
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
            Cukup satu kali. Token disimpan aman dan hanya digunakan untuk mengirim tiket.
        </p>

    </div>
</div>

{{-- =================== ALERT MODAL =================== --}}
<div id="alertModal" class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">
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
<div id="confirmModal" class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">
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
                Batal
            </button>
            <button id="confirmOkBtn"
                class="flex-1 py-2.5 bg-red-800 hover:bg-red-900 text-white text-sm font-semibold rounded-xl transition-colors">
                Ya, Lanjutkan
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
        title: 'Berhasil',
    },
    error: {
        bg: 'bg-red-100',
        icon: `<svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`,
        title: 'Terjadi Kesalahan',
    },
    info: {
        bg: 'bg-blue-100',
        icon: `<svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        title: 'Informasi',
    },
    warning: {
        bg: 'bg-amber-100',
        icon: `<svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
        title: 'Peringatan',
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
    document.getElementById('alertModal').classList.remove('hidden');
}

function closeAlertModal() {
    document.getElementById('alertModal').classList.add('hidden');
    if (typeof _alertCallback === 'function') {
        const cb = _alertCallback;
        _alertCallback = null;
        cb();
    }
}

// ===== Confirm Modal =====
let _confirmResolve = null;

function showConfirm(message, title = 'Konfirmasi') {
    return new Promise(resolve => {
        _confirmResolve = resolve;
        document.getElementById('confirmTitle').textContent   = title;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmModal').classList.remove('hidden');
    });
}

function resolveConfirm(value) {
    document.getElementById('confirmModal').classList.add('hidden');
    if (_confirmResolve) {
        const r = _confirmResolve;
        _confirmResolve = null;
        r(value);
    }
}

document.getElementById('confirmCancelBtn').addEventListener('click', () => resolveConfirm(false));
document.getElementById('confirmOkBtn').addEventListener('click',     () => resolveConfirm(true));

// ===== OAuth Status =====
let emailLinked = false;

async function loadEmailStatus() {
    try {
        const res  = await fetch('{{ route("oauth.email.status") }}');
        const data = await res.json();

        const statusEl      = document.getElementById('fromStatus');
        const linkBtn       = document.getElementById('linkEmailBtn');
        const changeBtn     = document.getElementById('changeEmailBtn');
        const disconnectBtn = document.getElementById('disconnectBtn');

        if (data.linked && !data.expired) {
            emailLinked = true;

            const providerIcon = data.provider === 'google'
                ? `<svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>`
                : `<svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path d="M11.4 2H2v9.4h9.4V2z" fill="#F25022"/><path d="M22 2h-9.4v9.4H22V2z" fill="#7FBA00"/><path d="M11.4 12.6H2V22h9.4v-9.4z" fill="#00A4EF"/><path d="M22 12.6h-9.4V22H22v-9.4z" fill="#FFB900"/></svg>`;

            statusEl.innerHTML = `
                <div class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-sm px-3 py-1 rounded-full border border-green-200">
                    ${providerIcon}
                    <span class="font-medium">${data.email}</span>
                </div>`;

            changeBtn.classList.remove('hidden');
            disconnectBtn.classList.remove('hidden');
        } else {
            emailLinked = false;

            // Tampilkan info opsional — tidak memblokir submit
            statusEl.innerHTML = `
                <div class="inline-flex items-center gap-1.5 text-gray-400 text-xs">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Tidak ada email terhubung — ticket akan dibuat tanpa email</span>
                </div>`;

            linkBtn.classList.remove('hidden');
        }
    } catch (err) {
        console.error('Failed to load email status', err);
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
    const ok = await showConfirm('Akun email yang terhubung akan dilepas.', 'Lepas akun email?');
    if (!ok) return;

    const res  = await fetch('{{ route("oauth.email.disconnect") }}', {
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
        document.getElementById('changeEmailBtn').classList.add('hidden');
        document.getElementById('disconnectBtn').classList.add('hidden');
        loadEmailStatus();
        showAlert('Akun email berhasil dilepas.', 'info');
    }
}

async function discardTicket() {
    const ok = await showConfirm('Perubahan yang belum disimpan akan hilang.', 'Buang tiket ini?');
    if (ok) window.location.href = '{{ route("tickets.index") }}';
}

// ===== Form Submit =====
document.getElementById('composeForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const subject  = document.getElementById('subject').value.trim();
    const body     = document.getElementById('body').value.trim();
    const priority = document.getElementById('ticket_priority').value;

    if (!subject) { showAlert('Subject tidak boleh kosong.', 'warning'); return; }
    if (!body)    { showAlert('Isi pesan tidak boleh kosong.', 'warning'); return; }

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
            }),
        });

        const data = await res.json();

        if (data.success) {
            let msg, title;
            if (data.staging) {
                title = 'Tiket Terkirim';
                msg   = data.email_sent
                    ? 'Tiket sedang menunggu validasi admin. Email notifikasi telah dikirim ke inbox Anda.'
                    : 'Tiket sedang menunggu validasi admin.';
            } else {
                title = 'Tiket Dibuat';
                msg   = 'Tiket berhasil dibuat.';
            }
            showAlert(msg, 'success', title, () => {
                window.location.href = '{{ route("tickets.index") }}';
            });
        } else {
            showAlert(data.message || 'Gagal mengirim tiket. Coba lagi.', 'error');
            setLoading(false);
        }
    } catch (err) {
        console.error(err);
        showAlert('Terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.', 'error');
        setLoading(false);
    }
});

function setLoading(loading) {
    const btn = document.getElementById('sendBtn');
    btn.disabled = loading;
    document.getElementById('sendIcon').classList.toggle('hidden', loading);
    document.getElementById('sendSpinner').classList.toggle('hidden', !loading);
    document.getElementById('sendText').textContent = loading ? 'Sending...' : 'Send';
}

// ===== Flash session dari OAuth callback =====
@if(session('oauth_success'))
document.addEventListener('DOMContentLoaded', () => showAlert(@json(session('oauth_success')), 'success', 'Email Terhubung'));
@endif
@if(session('oauth_error'))
document.addEventListener('DOMContentLoaded', () => showAlert(@json(session('oauth_error')), 'error', 'Gagal Menghubungkan'));
@endif

loadEmailStatus();
</script>
@endpush
