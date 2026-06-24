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

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 4px 8px !important;
    background: #f9fafb;
    border-radius: 0 !important;
}
.ql-container.ql-snow {
    border: none !important;
    font-size: 14px;
    font-family: inherit;
}
.ql-editor {
    min-height: 160px;
    max-height: 360px;
    overflow-y: auto;
    padding: 10px 14px;
    color: #1f2937;
    line-height: 1.6;
}
.ql-editor.ql-blank::before {
    color: #9ca3af;
    font-style: normal;
    font-size: 14px;
    left: 14px;
}
#quillWrapper {
    border: 1px solid #d1d5db;
    border-radius: 0.75rem;
    overflow: hidden;
    transition: box-shadow 0.15s, border-color 0.15s;
}
#quillWrapper.focused {
    border-color: transparent;
    box-shadow: 0 0 0 2px #991b1b;
}
.attach-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 9999px;
    padding: 3px 10px 3px 8px;
    font-size: 12px;
    color: #1e40af;
    max-width: 200px;
}
.attach-chip span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
@endpush

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">

        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">New Support Ticket</h2>
            <p class="text-sm text-gray-400 mt-0.5">Our support team will review and respond to your request.</p>
        </div>

        {{-- Form --}}
        <form id="ticketForm" class="px-6 py-6 space-y-5">
            @csrf

            @if($isParentCustomer)
            {{-- End Customer Selector (hanya muncul untuk parent customer) --}}
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl space-y-3">
                <div class="flex items-center gap-2 text-blue-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-semibold">You are submitting this ticket on behalf of an end customer.</span>
                </div>
                <div>
                    <label for="for_customer_id" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Ticket For <span class="text-red-500">*</span>
                    </label>
                    <select id="for_customer_id" name="for_customer_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                        <option value="">— Select end customer —</option>
                        @foreach($endCustomers as $ec)
                        <option value="{{ $ec['id'] }}">{{ $ec['name'] }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">The ticket will remain under your account. This selection tells Eclectic which end customer this ticket is for.</p>
                </div>
            </div>
            @endif

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

            {{-- Ticket Type, Scale & Priority --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="ticket_type" class="block text-sm font-semibold text-gray-700 mb-1.5">Ticket Type</label>
                    <select id="ticket_type" name="ticket_type"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                        <option value="">— Select type —</option>
                        <option value="Incident">Incident</option>
                        <option value="Service Request">Service Request</option>
                        <option value="Change Request">Change Request</option>
                        <option value="Consult">Consult</option>
                    </select>
                </div>
                <div>
                    <label for="scale" class="block text-sm font-semibold text-gray-700 mb-1.5">Scale</label>
                    <select id="scale" name="scale"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                        <option value="">— Select scale —</option>
                        <option value="Simple">Simple</option>
                        <option value="Medium">Medium</option>
                        <option value="Complex">Complex</option>
                    </select>
                </div>
                <div>
                    <label for="ticket_priority" class="block text-sm font-semibold text-gray-700 mb-1.5">Priority</label>
                    <select id="ticket_priority" name="ticket_priority"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
                        <option value="Very High">Very High</option>
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>

            {{-- CC — row-based, default 0 rows --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        CC
                        <span class="text-xs font-normal text-gray-400 ml-1">(optional)</span>
                    </label>
                    <button type="button" onclick="addCcRow()"
                        class="flex items-center gap-1 text-xs font-medium text-red-700 hover:text-red-900 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add CC
                    </button>
                </div>
                <div id="ccRows" class="space-y-2"></div>
                <p id="ccError" class="text-xs text-red-600 mt-1 hidden"></p>
            </div>

            {{-- Details — Quill rich text editor --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Details <span class="text-red-500">*</span>
                </label>
                <div id="quillWrapper">
                    <div id="detailsEditor"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">You can paste images directly into the editor.</p>
            </div>

            {{-- Attachments --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        Attachments
                        <span class="text-xs font-normal text-gray-400 ml-1">(optional)</span>
                    </label>
                    <button type="button" onclick="document.getElementById('attachInput').click()"
                        class="flex items-center gap-1 text-xs font-medium text-red-700 hover:text-red-900 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        Add File
                    </button>
                </div>
                <input type="file" id="attachInput" multiple class="hidden"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.csv">
                <div id="attachPreview" class="flex flex-wrap gap-2"></div>
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
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const IS_PARENT_CUSTOMER = {{ $isParentCustomer ? 'true' : 'false' }};

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

async function discardTicket() {
    const ok = await showConfirm('Unsaved changes will be lost.', 'Discard this ticket?');
    if (ok) window.location.href = '{{ route("tickets.index") }}';
}

// ===== Field Error Helpers =====
const FIELD_ERROR_CLASS = ['border-red-500', 'ring-2', 'ring-red-200'];
const FIELD_OK_CLASS    = ['border-gray-300'];

function setFieldError(el, hasError) {
    if (!el) return;
    if (hasError) {
        el.classList.remove(...FIELD_OK_CLASS);
        el.classList.add(...FIELD_ERROR_CLASS);
    } else {
        el.classList.remove(...FIELD_ERROR_CLASS);
        el.classList.add(...FIELD_OK_CLASS);
    }
}

function setQuillError(hasError) {
    const wrapper = document.getElementById('quillWrapper');
    if (hasError) {
        wrapper.style.boxShadow = '0 0 0 2px #fca5a5';
        wrapper.style.borderColor = '#ef4444';
    } else {
        wrapper.style.boxShadow = '';
        wrapper.style.borderColor = '';
    }
}

function clearAllFieldErrors() {
    setFieldError(document.getElementById('subject'), false);
    setQuillError(false);
    document.querySelectorAll('#ccRows input[type="email"]').forEach(i => setFieldError(i, false));
    if (IS_PARENT_CUSTOMER) setFieldError(document.getElementById('for_customer_id'), false);
}

// ===== CC Rows =====
const MAX_CC = 10;
const MAX_FILE_SIZE_MB = 20;
const MAX_FILES = 10;
let ccRowCount = 0;

function addCcRow() {
    const activeCcCount = document.querySelectorAll('#ccRows input[type="email"]').length;
    if (activeCcCount >= MAX_CC) {
        showToast(`You can add a maximum of ${MAX_CC} CC recipients per ticket.`, 'warning', 'CC Limit Reached');
        return;
    }
    document.getElementById('ccError').classList.add('hidden');
    ccRowCount++;
    const id = 'ccRow_' + ccRowCount;
    const row = document.createElement('div');
    row.id = id;
    row.className = 'flex items-center gap-2';
    row.innerHTML = `
        <input type="email" name="cc_emails[]" placeholder="email@example.com"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400
                   focus:outline-none focus:ring-2 focus:ring-red-800 focus:border-transparent transition-all">
        <button type="button" onclick="removeCcRow('${id}')"
            class="p-1.5 text-gray-400 hover:text-red-600 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>`;
    document.getElementById('ccRows').appendChild(row);
    row.querySelector('input').focus();
}
function removeCcRow(id) {
    const row = document.getElementById(id);
    if (row) row.remove();
}

// ===== Quill Editor =====
const quill = new Quill('#detailsEditor', {
    theme: 'snow',
    placeholder: 'Describe your issue in detail. Include steps to reproduce, error messages, or screenshots if relevant...',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['blockquote', 'code-block'],
            ['link', 'image'],
            ['clean'],
        ],
    },
});

quill.on('selection-change', (range) => {
    const wrapper = document.getElementById('quillWrapper');
    if (range) {
        wrapper.classList.add('focused');
        setQuillError(false);
    } else {
        wrapper.classList.remove('focused');
    }
});

// Clear subject error on input
document.getElementById('subject').addEventListener('input', function () {
    setFieldError(this, false);
});

// ===== Attachments =====
const selectedFiles = [];

document.getElementById('attachInput').addEventListener('change', function () {
    const incoming = Array.from(this.files);

    for (const file of incoming) {
        // Duplicate check
        if (selectedFiles.find(f => f.name === file.name && f.size === file.size)) continue;

        // Per-file size limit: 20 MB
        if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
            showToast(
                `"${file.name}" exceeds the ${MAX_FILE_SIZE_MB} MB file size limit and was not added.`,
                'error', 'File Too Large'
            );
            continue;
        }

        // Total file count limit
        if (selectedFiles.length >= MAX_FILES) {
            showToast(
                `You can attach a maximum of ${MAX_FILES} files per ticket. Additional files were skipped.`,
                'warning', 'Attachment Limit Reached'
            );
            break;
        }

        selectedFiles.push(file);
    }

    this.value = '';
    renderAttachPreview();
});

function renderAttachPreview() {
    const container = document.getElementById('attachPreview');
    container.innerHTML = '';
    selectedFiles.forEach((file, idx) => {
        const chip = document.createElement('div');
        chip.className = 'attach-chip';
        chip.innerHTML = `
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <span title="${escH(file.name)}">${escH(file.name)}</span>
            <span class="text-blue-400 shrink-0">${fmtSize(file.size)}</span>
            <button type="button" onclick="removeAttach(${idx})" class="shrink-0 text-blue-300 hover:text-red-500 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>`;
        container.appendChild(chip);
    });
}
function removeAttach(idx) {
    selectedFiles.splice(idx, 1);
    renderAttachPreview();
}
function fmtSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}
function escH(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ===== Client-Side Validation =====
function validateForm() {
    clearAllFieldErrors();

    // Validasi end customer (hanya untuk parent customer)
    if (IS_PARENT_CUSTOMER) {
        const forEl = document.getElementById('for_customer_id');
        if (!forEl?.value) {
            setFieldError(forEl, true);
            showToast('Please select the end customer this ticket is for.', 'warning', 'End Customer Required');
            forEl?.focus();
            return false;
        }
    }

    const subject  = document.getElementById('subject').value.trim();
    const bodyText = quill.getText().trim();

    if (!subject) {
        setFieldError(document.getElementById('subject'), true);
        showToast('Please provide a subject for your ticket.', 'warning', 'Subject Required');
        document.getElementById('subject').focus();
        return false;
    }

    if (subject.length < 5) {
        setFieldError(document.getElementById('subject'), true);
        showToast('Subject must be at least 5 characters long.', 'warning', 'Subject Too Short');
        document.getElementById('subject').focus();
        return false;
    }

    if (subject.length > 5000) {
        setFieldError(document.getElementById('subject'), true);
        showToast('Subject cannot exceed 5,000 characters.', 'warning', 'Subject Too Long');
        document.getElementById('subject').focus();
        return false;
    }

    if (!bodyText || bodyText.length < 10) {
        setQuillError(true);
        showToast('Please describe your issue in detail (at least 10 characters).', 'warning', 'Details Required');
        quill.focus();
        return false;
    }

    // Validate CC emails
    const ccInputEls = document.querySelectorAll('#ccRows input[type="email"]');
    const ccEmails   = [];
    for (const inp of ccInputEls) {
        const val = inp.value.trim();
        if (!val) continue;
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            setFieldError(inp, true);
            inp.focus();
            showToast(
                `"${escH(val)}" is not a valid email address. Please enter a properly formatted email (e.g. name@example.com).`,
                'warning', 'Invalid CC Email'
            );
            return false;
        }
        // Check for duplicate CC
        if (ccEmails.includes(val.toLowerCase())) {
            setFieldError(inp, true);
            showToast(`Duplicate CC address: "${escH(val)}". Each recipient must be unique.`, 'warning', 'Duplicate CC');
            return false;
        }
        ccEmails.push(val.toLowerCase());
    }

    return { subject, bodyText, bodyHtml: quill.root.innerHTML.trim(), ccEmails };
}

// ===== Server Error Parser =====
function parseServerError(res, data) {
    // 401 — session expired
    if (res.status === 401) {
        showToast('Your session has expired. Please log in again to continue.', 'error', 'Session Expired');
        setTimeout(() => { window.location.href = '{{ route("login") }}'; }, 2500);
        return;
    }

    // 403 — forbidden
    if (res.status === 403) {
        showToast('You do not have permission to submit tickets. Please contact your administrator.', 'error', 'Access Denied');
        return;
    }

    // 422 — validation errors from server
    if (res.status === 422 && data?.errors) {
        const fieldMessages = {
            description:     'Subject field is required and must not exceed 5,000 characters.',
            body:            'Please provide a description of your issue.',
            ticket_priority: 'Invalid priority selected. Choose from Very High, High, Medium, or Low.',
            'cc_emails':     'One or more CC email addresses are invalid.',
            'cc_emails.*':   'One or more CC email addresses are invalid.',
            'attachments':   'One or more attachments are invalid.',
            'attachments.*': 'Each attachment must not exceed 20 MB and must be a supported file type.',
        };
        const errors = data.errors;
        const firstKey = Object.keys(errors)[0];
        const friendlyMsg = fieldMessages[firstKey] || errors[firstKey]?.[0] || 'Please review your input and try again.';
        showToast(friendlyMsg, 'error', 'Validation Error');
        return;
    }

    // 429 — rate limited
    if (res.status === 429) {
        showToast('Too many requests. Please wait a moment before submitting again.', 'warning', 'Rate Limit Reached');
        return;
    }

    // 500 / other server errors
    if (res.status >= 500) {
        showToast(
            'An internal server error occurred while processing your request. Please try again in a few moments or contact support if the issue persists.',
            'error', 'Server Error'
        );
        return;
    }

    // Generic server message
    const msg = data?.message;
    if (msg) {
        // Map common server messages to user-friendly text
        if (msg.toLowerCase().includes('email')) {
            showToast('Failed to send the notification email. Your ticket was saved but email delivery failed. Please contact support.', 'error', 'Email Delivery Failed');
        } else if (msg.toLowerCase().includes('unauthorized') || msg.toLowerCase().includes('unauthenticated')) {
            showToast('Authentication failed. Please log in and try again.', 'error', 'Unauthorized');
            setTimeout(() => { window.location.href = '{{ route("login") }}'; }, 2500);
        } else {
            showToast(msg, 'error', 'Submission Failed');
        }
        return;
    }

    showToast('An unexpected error occurred. Please try again or contact support if the issue persists.', 'error', 'Submission Failed');
}

// ===== Form Submit =====
document.getElementById('ticketForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const validated = validateForm();
    if (!validated) return;

    const { subject, bodyText, bodyHtml, ccEmails } = validated;
    const priority = document.getElementById('ticket_priority')?.value || 'Medium';

    setLoading(true);

    try {
        const fd = new FormData();
        fd.append('_token',          CSRF_TOKEN);
        fd.append('description',     subject);
        fd.append('body_html',       bodyHtml);
        fd.append('body',            bodyText);
        fd.append('ticket_priority', priority);
        const ticketTypeVal = document.getElementById('ticket_type')?.value;
        if (ticketTypeVal) fd.append('ticket_type', ticketTypeVal);
        const scaleVal = document.getElementById('scale')?.value;
        if (scaleVal) fd.append('scale', scaleVal);
        fd.append('name',   document.getElementById('name').value.trim());
        fd.append('no_hp',  document.getElementById('no_hp').value.trim());
        fd.append('module', document.getElementById('module').value.trim());
        fd.append('client', document.getElementById('client').value.trim());
        ccEmails.forEach(email => fd.append('cc_emails[]', email));
        selectedFiles.forEach(file => fd.append('attachments[]', file));
        if (IS_PARENT_CUSTOMER) {
            const forCustomerId = document.getElementById('for_customer_id')?.value;
            if (forCustomerId) fd.append('for_customer_id', forCustomerId);
        }

        let res, data;
        try {
            res  = await fetch('{{ route("tickets.store") }}', {
                method:  'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body:    fd,
            });
            data = await res.json();
        } catch (networkErr) {
            console.error('Network error:', networkErr);
            showToast(
                'Unable to reach the server. Please check your internet connection and try again.',
                'error', 'Connection Failed'
            );
            setLoading(false);
            return;
        }

        if (data.success) {
            // Success — show toast then redirect
            const isStaging = !!data.staging;
            const title = isStaging ? 'Ticket Submitted Successfully' : 'Ticket Created Successfully';
            const msg   = isStaging
                ? 'Your ticket has been submitted and is pending admin review. A confirmation email has been sent to your registered email address.'
                : 'Your support ticket has been created and assigned to our team. You will be notified once it is reviewed.';

            showToast(msg, 'success', title, 4000, () => {
                window.location.href = '{{ route("tickets.index") }}';
            });
            // Button stays disabled — user is being redirected
        } else {
            parseServerError(res, data);
            setLoading(false);
        }

    } catch (unexpectedErr) {
        console.error('Unexpected error:', unexpectedErr);
        showToast(
            'An unexpected client-side error occurred. Please refresh the page and try again.',
            'error', 'Unexpected Error'
        );
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

</script>
@endpush
