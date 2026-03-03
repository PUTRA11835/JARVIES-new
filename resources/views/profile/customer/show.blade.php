@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', 'Your account information')

@push('styles')
<style>
    /* Read-only mode: prevent editing in all section inputs */
    .section-content input:not([type=radio]):not([type=checkbox]),
    .section-content select,
    .section-content textarea {
        pointer-events: none !important;
        background: #f9fafb !important;
        cursor: default !important;
        color: #374151 !important;
    }
    /* Hide add / create / delete / edit / copy static action buttons */
    button[onclick*="openAdd"],
    button[onclick*="openCreate"],
    button[onclick*="deleteSelected"],
    button[onclick*="copySelected"],
    button[onclick*="saveCustomer"],
    button[onclick*="saveCurrentSection"] {
        display: none !important;
    }
    /* Hide add/edit modal containers */
    #bankAccountModal, #attachmentModal { display: none !important; }
    /* Search bars in attachment / history not needed for read-only */
    #attachmentSearch, #businessSearch, #projectSearch {
        pointer-events: none !important;
        background: #f9fafb !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- ── Profile Banner ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-start gap-6">
            {{-- Avatar --}}
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-red-800 to-red-950 text-white flex items-center justify-center font-bold text-3xl flex-shrink-0">
                @php
                    $name1    = $customer->basicData->name_1 ?? 'N';
                    $initials = strtoupper(substr($name1, 0, 1));
                    if (strlen($name1) > 1 && strpos($name1, ' ') !== false) {
                        $parts    = explode(' ', $name1);
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                    }
                @endphp
                {{ $initials }}
            </div>

            {{-- Info --}}
            <div class="flex-1">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $customer->basicData->name_1 ?? 'N/A' }}</h1>
                        @if(!empty($customer->basicData->name_2))
                        <p class="text-gray-500 mt-0.5">{{ $customer->basicData->name_2 }}</p>
                        @endif
                    </div>
                    @php
                        $statusClass = 'bg-gray-100 text-gray-800';
                        $statusLabel = 'Inactive';
                        if (!empty($customer->basicData->deletion_flag)) {
                            $statusClass = 'bg-red-100 text-red-800';
                            $statusLabel = 'Flagged for Deletion';
                        } elseif (!empty($customer->basicData->block)) {
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            $statusLabel = 'Blocked';
                        } elseif ($customer->is_active) {
                            $statusClass = 'bg-green-100 text-green-800';
                            $statusLabel = 'Active';
                        }
                    @endphp
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Customer Code</p>
                        <p class="font-semibold text-gray-900 font-mono">{{ $customer->customer_code ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Login Email</p>
                        <p class="font-semibold text-gray-900 break-all">{{ $authUser->email ?? $customer->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Member Since</p>
                        <p class="font-semibold text-gray-900">
                            {{ $authUser && $authUser->created_at ? \Carbon\Carbon::parse($authUser->created_at)->format('d M Y') : 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Customer Group</p>
                        <p class="font-semibold text-gray-900">{{ $customer->basicData->customer_group ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Customer Category</p>
                        <p class="font-semibold text-gray-900">{{ $customer->basicData->customer_category ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Industry Sector</p>
                        <p class="font-semibold text-gray-900">{{ $customer->basicData->industry_sector ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto">
                <button onclick="switchSection('basic-data')" data-section="basic-data"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-red-800 text-red-800 whitespace-nowrap">
                    Basic Data
                </button>
                <button onclick="switchSection('address')" data-section="address"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Address
                </button>
                <button onclick="switchSection('contact')" data-section="contact"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Contact Person
                </button>
                <button onclick="switchSection('identification')" data-section="identification"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Identification
                </button>
                <button onclick="switchSection('bank')" data-section="bank"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Bank Account
                </button>
                <button onclick="switchSection('attachment')" data-section="attachment"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Attachment
                </button>
                <button onclick="switchSection('change-password')" data-section="change-password"
                        class="section-tab px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-600 hover:text-red-800 hover:border-gray-300 whitespace-nowrap">
                    Change Password
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">

            <div id="section-basic-data" class="section-content">
                @include('profile.customer.sections.basicdata', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            <div id="section-address" class="section-content hidden">
                @include('profile.customer.sections.address', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            <div id="section-contact" class="section-content hidden">
                @include('profile.customer.sections.contact', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            <div id="section-identification" class="section-content hidden">
                @include('profile.customer.sections.identification', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            <div id="section-bank" class="section-content hidden">
                @include('profile.customer.sections.bank', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            <div id="section-attachment" class="section-content hidden">
                @include('profile.customer.sections.attachment', ['customer' => $customer, 'customerId' => $customer->customer_id])
            </div>

            {{-- Change Password (email-based flow) --}}
            <div id="section-change-password" class="section-content hidden">
                <div class="max-w-md">
                    <h3 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Change Password</h3>

                    @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg text-sm font-medium bg-red-50 text-red-700 border border-red-200">
                        {{ session('error') }}
                    </div>
                    @endif

                    <p class="text-sm text-gray-600 mb-6">
                        To change your password, we will send a secure reset link to your registered email address.
                        Click the link in the email to set a new password.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-xs font-semibold text-gray-500 mb-1">Registered Email</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $authUser->email ?? 'No email registered' }}</p>
                    </div>

                    @if($authUser && !empty($authUser->email))
                    <form method="POST" action="{{ route('profile.send-reset-link') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-red-800 hover:bg-red-900 text-white text-sm font-semibold py-2.5 px-6 rounded-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send Password Reset Link
                        </button>
                    </form>
                    @else
                    <p class="text-sm text-red-600">No email address is associated with your account. Please contact support.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const customerId = {{ $customer->customer_id }};

    // ── Tab switching ──
    function switchSection(sectionName) {
        document.querySelectorAll('.section-content').forEach(function(el) {
            el.classList.add('hidden');
        });
        document.querySelectorAll('.section-tab').forEach(function(tab) {
            tab.classList.remove('border-red-800', 'text-red-800');
            tab.classList.add('border-transparent', 'text-gray-600');
        });

        var section = document.getElementById('section-' + sectionName);
        if (section) section.classList.remove('hidden');

        var tab = document.querySelector('[data-section="' + sectionName + '"]');
        if (tab) {
            tab.classList.add('border-red-800', 'text-red-800');
            tab.classList.remove('border-transparent', 'text-gray-600');
        }
    }

    // ── Shared helpers (referenced by included section scripts) ──
    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value || '';
    }
    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }
    function showNotification(message, type) {
        // Suppress AJAX error notifications in read-only profile view
        if (type === 'error') return;
        var bg = type === 'success' ? 'bg-green-500' : 'bg-blue-500';
        var n = document.createElement('div');
        n.className = 'fixed top-4 right-4 ' + bg + ' text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300';
        n.textContent = message;
        document.body.appendChild(n);
        setTimeout(function() {
            n.style.opacity = '0';
            setTimeout(function() { n.remove(); }, 300);
        }, 3000);
    }

    // ── Override all mutating functions from included sections (read-only mode) ──
    document.addEventListener('DOMContentLoaded', function() {
        var noOp = function() {};
        // Bank
        window.openAddBankModal     = noOp;
        window.editBankAccount      = noOp;
        window.deleteBankAccount    = noOp;
        window.saveBankAccount      = noOp;
        window.editFromDetail       = noOp;
        // Attachment
        window.openCreateAttachmentModal = noOp;
        window.saveAttachmentFromModal   = noOp;
        window.copySelectedAttachment    = noOp;
        window.deleteSelectedAttachment  = noOp;
        // History
        window.openCreateBusinessModal = noOp;
        window.deleteSelectedBusiness  = noOp;
        window.copySelectedBusiness    = noOp;
        window.openCreateProjectModal  = noOp;
        window.deleteSelectedProject   = noOp;
        window.copySelectedProject     = noOp;
        // Contact / Address / Identification (save actions)
        window.saveContacts          = noOp;
        window.saveAddresses         = noOp;
        window.saveIdentifications   = noOp;
        window.saveCurrentSection    = noOp;
        // Settings buttons inside sections — hide via JS (catch dynamic renders)
        setTimeout(function() {
            document.querySelectorAll(
                '[onclick*="openAdd"], [onclick*="openCreate"], [onclick*="delete"], ' +
                '[onclick*="copySelected"], [onclick*="saveCurrentSection"], ' +
                '[title="Settings"], [title="Export"]'
            ).forEach(function(el) { el.style.display = 'none'; });
        }, 500);

        // Auto-switch to Change Password tab if there is a session error from sendResetLink
        @if(session('error'))
        switchSection('change-password');
        @endif
    });

</script>
@endpush
