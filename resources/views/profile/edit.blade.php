@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', 'View your account information')

@push('styles')
<style>
    .info-row { display: flex; flex-direction: column; gap: 2px; }
    .info-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; }
    .info-value { font-size: 0.925rem; font-weight: 500; color: #111827; }
    .info-value.empty { color: #d1d5db; font-style: italic; font-weight: 400; }
    .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
    .section-header { padding: 18px 24px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap-10px; }
    .badge-active { display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: #dcfce7; color: #15803d; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Top Avatar + Name Banner ── --}}
    <div class="section-card">
        <div class="bg-gradient-to-r from-red-800 to-red-950 px-8 py-8">
            <div class="flex items-center gap-6">
                {{-- Avatar --}}
                <div class="w-20 h-20 rounded-2xl bg-white/20 border-2 border-white/30 flex items-center justify-center text-white text-3xl font-bold shrink-0 shadow-lg">
                    {{ strtoupper(substr($profile->name_1 ?? session('user.company_name', 'U'), 0, 2)) }}
                </div>
                {{-- Name + Code + Status --}}
                <div class="flex-1 min-w-0">
                    <h2 class="text-white text-2xl font-bold leading-tight truncate">
                        {{ $profile->name_1 ?? session('user.company_name', '—') }}
                    </h2>
                    @if(!empty($profile->name_2))
                    <p class="text-red-200 text-sm mt-0.5">{{ $profile->name_2 }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-3 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/15 text-white px-3 py-1.5 rounded-full border border-white/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            {{ $profile->customer_code ?? '—' }}
                        </span>
                        @if($profile->is_active)
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-green-400/20 text-green-200 px-3 py-1.5 rounded-full border border-green-400/30">
                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                            Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-gray-400/20 text-gray-200 px-3 py-1.5 rounded-full border border-gray-400/30">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                            Inactive
                        </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Customer
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── LEFT COLUMN: Account Info + Contact ── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Account Information --}}
            <div class="section-card">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">Account Information</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="info-row">
                        <span class="info-label">Company Name</span>
                        <span class="info-value">{{ $profile->name_1 ?? '—' }}</span>
                    </div>
                    @if(!empty($profile->name_2))
                    <div class="info-row">
                        <span class="info-label">Name 2</span>
                        <span class="info-value">{{ $profile->name_2 }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Customer Code</span>
                        <span class="info-value font-mono text-red-800">{{ $profile->customer_code ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Title / Salutation</span>
                        <span class="info-value {{ empty($profile->title) ? 'empty' : '' }}">{{ $profile->title ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Customer Group</span>
                        <span class="info-value {{ empty($profile->customer_group) ? 'empty' : '' }}">{{ $profile->customer_group ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Customer Category</span>
                        <span class="info-value {{ empty($profile->customer_category) ? 'empty' : '' }}">{{ $profile->customer_category ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Industry Sector</span>
                        <span class="info-value {{ empty($profile->industry_sector) ? 'empty' : '' }}">{{ $profile->industry_sector ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Executive</span>
                        <span class="info-value {{ empty($profile->ec_account_executive) ? 'empty' : '' }}">{{ $profile->ec_account_executive ?: 'Not assigned' }}</span>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="section-card">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">Contact Information</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="info-row">
                        <span class="info-label">Login Email</span>
                        <span class="info-value">{{ $authUser->email ?? $profile->email ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Business Email</span>
                        <span class="info-value {{ empty($profile->email) ? 'empty' : '' }}">{{ $profile->email ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value {{ empty($authUser->phone) ? 'empty' : '' }}">{{ $authUser->phone ?: 'Not set' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Username</span>
                        <span class="info-value font-mono text-sm {{ empty($authUser->username) ? 'empty' : '' }}">{{ $authUser->username ?: 'Not set' }}</span>
                    </div>
                    @if(!empty($profile->contact_name))
                    <div class="info-row">
                        <span class="info-label">Contact Person</span>
                        <span class="info-value">{{ $profile->contact_name }}</span>
                    </div>
                    @endif
                    @if(!empty($profile->contact_phone))
                    <div class="info-row">
                        <span class="info-label">Contact Phone</span>
                        <span class="info-value">{{ $profile->contact_phone }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── RIGHT COLUMN: Account Activity + Change Password ── --}}
        <div class="space-y-6">

            {{-- Account Activity --}}
            <div class="section-card">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">Account Activity</h3>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="info-row">
                        <span class="info-label">Member Since</span>
                        <span class="info-value">
                            {{ $authUser && $authUser->created_at ? \Carbon\Carbon::parse($authUser->created_at)->format('d M Y') : '—' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Login</span>
                        <span class="info-value">
                            @if($authUser && $authUser->last_login_at)
                                {{ \Carbon\Carbon::parse($authUser->last_login_at)->format('d M Y, H:i') }}
                            @else
                                <span class="empty">No record</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Status</span>
                        <div class="mt-1">
                            @if($profile->is_active)
                            <span class="badge-active">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Active
                            </span>
                            @else
                            <span class="badge-inactive badge-active">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Inactive
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="section-card">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-sm">Change Password</h3>
                </div>
                <div class="px-6 py-5">
                    {{-- Alert area --}}
                    <div id="pwAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

                    <form id="pwForm" class="space-y-4" onsubmit="submitChangePassword(event)">
                        @csrf
                        <div>
                            <label class="info-label mb-1 block">Current Password</label>
                            <input type="password" name="current_password" id="currentPassword"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 transition-all"
                                   placeholder="••••••••">
                        </div>
                        <div>
                            <label class="info-label mb-1 block">New Password</label>
                            <input type="password" name="password" id="newPassword"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 transition-all"
                                   placeholder="••••••••">
                        </div>
                        <div>
                            <label class="info-label mb-1 block">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="confirmPassword"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 transition-all"
                                   placeholder="••••••••">
                        </div>
                        <button type="submit" id="pwBtn"
                                class="w-full bg-red-800 hover:bg-red-900 text-white text-sm font-semibold py-2.5 rounded-lg transition-all">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function submitChangePassword(e) {
    e.preventDefault();

    var btn  = document.getElementById('pwBtn');
    var alert = document.getElementById('pwAlert');

    btn.disabled    = true;
    btn.textContent = 'Updating…';
    alert.className = 'hidden mb-4 p-3 rounded-lg text-sm font-medium';

    var formData = new FormData(document.getElementById('pwForm'));

    fetch('{{ route('profile.change-password') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
        if (res.ok && res.data.success) {
            alert.className = 'mb-4 p-3 rounded-lg text-sm font-medium bg-green-50 text-green-700 border border-green-200';
            alert.textContent = res.data.message || 'Password updated successfully.';
            document.getElementById('pwForm').reset();
        } else {
            var msg = res.data.message || 'Failed to update password.';
            if (res.data.errors) {
                msg = Object.values(res.data.errors).flat().join(' ');
            }
            alert.className = 'mb-4 p-3 rounded-lg text-sm font-medium bg-red-50 text-red-700 border border-red-200';
            alert.textContent = msg;
        }
    })
    .catch(function() {
        alert.className = 'mb-4 p-3 rounded-lg text-sm font-medium bg-red-50 text-red-700 border border-red-200';
        alert.textContent = 'A network error occurred. Please try again.';
    })
    .finally(function() {
        btn.disabled    = false;
        btn.textContent = 'Update Password';
    });
}
</script>
@endpush
