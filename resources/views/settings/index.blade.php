@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your account and preferences')

@push('styles')
<style>
    .settings-tab {
        border-bottom-width: 2px;
        border-bottom-style: solid;
        white-space: nowrap;
        transition: color 0.15s, border-color 0.15s;
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .settings-tab.inactive {
        border-color: transparent;
        color: #6b7280;
    }
    .settings-tab.inactive:hover { color: #1f2937; }
    .settings-tab.active {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    .settings-content { display: none; }
    .settings-content.active { display: block; }
    .theme-option {
        cursor: pointer;
        border-width: 2px;
        border-style: solid;
        border-color: #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.25rem;
        text-align: center;
        transition: border-color 0.15s, background 0.15s;
    }
    .theme-option:hover { border-color: var(--primary-color); }
    .theme-option.selected {
        border-color: var(--primary-color);
        background: #fef2f2;
    }
    .toggle-track {
        position: relative;
        display: inline-flex;
        align-items: center;
        width: 2.75rem;
        height: 1.5rem;
        background: #d1d5db;
        border-radius: 9999px;
        cursor: pointer;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .toggle-track.on { background: var(--primary-color); }
    .toggle-thumb {
        position: absolute;
        left: 2px;
        width: 1.25rem;
        height: 1.25rem;
        background: white;
        border-radius: 9999px;
        transition: transform 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-track.on .toggle-thumb { transform: translateX(1.25rem); }
</style>
@endpush

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <p class="text-sm text-gray-500">Customize appearance, preferences, and notifications</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="resetSettings()"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-all">
                Reset to Default
            </button>
            <button onclick="saveSettings()"
                    class="px-5 py-2 text-white text-sm font-medium rounded-lg transition-all shadow-sm hover:opacity-90"
                    style="background-color: var(--primary-color);">
                Save Changes
            </button>
        </div>
    </div>

    <!-- Tab Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 overflow-x-auto">
            <nav class="flex -mb-px">
                <button onclick="switchTab('appearance')"  data-tab="appearance"  class="settings-tab active">Appearance</button>
                <button onclick="switchTab('preferences')" data-tab="preferences" class="settings-tab inactive">Preferences</button>
                <button onclick="switchTab('notifications')" data-tab="notifications" class="settings-tab inactive">Notifications</button>
                <button onclick="switchTab('security')"    data-tab="security"    class="settings-tab inactive">Security</button>
            </nav>
        </div>

        <!-- ── Appearance ── -->
        <div id="tab-appearance" class="settings-content active p-6">

            <!-- Theme Mode -->
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Theme Mode</h3>
                <p class="text-xs text-gray-400 mb-4">Choose how the interface looks</p>
                <div class="grid grid-cols-3 gap-4 max-w-sm">
                    <div onclick="selectTheme('light')" class="theme-option" id="theme-light">
                        <div class="text-2xl mb-1">☀️</div>
                        <div class="text-sm font-semibold text-gray-900">Light</div>
                        <div class="text-xs text-gray-400">Bright & clean</div>
                    </div>
                    <div onclick="selectTheme('dark')" class="theme-option" id="theme-dark">
                        <div class="text-2xl mb-1">🌙</div>
                        <div class="text-sm font-semibold text-gray-900">Dark</div>
                        <div class="text-xs text-gray-400">Easy on eyes</div>
                    </div>
                    <div onclick="selectTheme('auto')" class="theme-option" id="theme-auto">
                        <div class="text-2xl mb-1">🖥️</div>
                        <div class="text-sm font-semibold text-gray-900">System</div>
                        <div class="text-xs text-gray-400">Follow device</div>
                    </div>
                </div>
            </div>

            <!-- Primary Color -->
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Accent Color</h3>
                <p class="text-xs text-gray-400 mb-4">Used for buttons, links, and highlights</p>
                <div class="flex items-center gap-4">
                    <input type="color" id="primaryColorPicker"
                           class="w-12 h-12 rounded-lg cursor-pointer border border-gray-200 p-0.5"
                           value="#c62828"
                           oninput="previewColor(this.value)">
                    <div>
                        <div class="text-sm font-medium text-gray-700">Custom color</div>
                        <div class="text-xs text-gray-400" id="colorHexLabel">#c62828</div>
                    </div>
                    <!-- Color presets -->
                    <div class="flex items-center gap-2 ml-4">
                        <button onclick="setColorPreset('#c62828')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#c62828;" title="Red"></button>
                        <button onclick="setColorPreset('#1565c0')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#1565c0;" title="Blue"></button>
                        <button onclick="setColorPreset('#2e7d32')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#2e7d32;" title="Green"></button>
                        <button onclick="setColorPreset('#6a1b9a')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#6a1b9a;" title="Purple"></button>
                        <button onclick="setColorPreset('#e65100')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#e65100;" title="Orange"></button>
                        <button onclick="setColorPreset('#00695c')" class="w-7 h-7 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform" style="background:#00695c;" title="Teal"></button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Preferences ── -->
        <div id="tab-preferences" class="settings-content p-6">

            <!-- Font Size -->
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1">Font Size</h3>
                <p class="text-xs text-gray-400 mb-4">Adjust text size across the interface</p>
                <div class="flex items-center gap-3">
                    <button onclick="selectFontSize('small')"  data-fs="small"  class="font-size-btn px-4 py-2 border-2 border-gray-200 rounded-lg text-sm text-gray-600 hover:border-gray-400 transition-all">Small</button>
                    <button onclick="selectFontSize('medium')" data-fs="medium" class="font-size-btn px-4 py-2 border-2 border-gray-200 rounded-lg text-sm text-gray-600 hover:border-gray-400 transition-all">Medium</button>
                    <button onclick="selectFontSize('large')"  data-fs="large"  class="font-size-btn px-4 py-2 border-2 border-gray-200 rounded-lg text-sm text-gray-600 hover:border-gray-400 transition-all">Large</button>
                </div>
            </div>

            <!-- Compact Mode -->
            <div class="mb-6 flex items-center justify-between max-w-lg">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">Compact Mode</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Reduce spacing for a denser layout</p>
                </div>
                <div class="toggle-track" id="compactToggle" onclick="togglePref('compact_mode', 'compactToggle')">
                    <div class="toggle-thumb"></div>
                </div>
            </div>

            <!-- Animations -->
            <div class="mb-6 flex items-center justify-between max-w-lg">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">Show Animations</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Enable smooth transitions and effects</p>
                </div>
                <div class="toggle-track on" id="animationsToggle" onclick="togglePref('show_animations', 'animationsToggle')">
                    <div class="toggle-thumb"></div>
                </div>
            </div>

        </div>

        <!-- ── Notifications ── -->
        <div id="tab-notifications" class="settings-content p-6">

            <div class="space-y-6 max-w-lg">

                <!-- Enable Notifications -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Enable Notifications</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Show bell badge and in-app alerts for ticket updates</p>
                    </div>
                    <div class="toggle-track on" id="notifEnabledToggle" onclick="togglePref('notifications_enabled', 'notifEnabledToggle')">
                        <div class="toggle-thumb"></div>
                    </div>
                </div>

                <hr class="border-gray-100">

                <!-- Info box -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    <div class="font-semibold mb-1">How notifications work</div>
                    <p class="text-xs text-blue-600 leading-relaxed">
                        JARVIES checks your tickets every 30 seconds. When an EcoSystem agent replies to a ticket
                        or changes its status, you will receive an in-app notification and the bell icon will show
                        the number of unread updates.
                    </p>
                </div>

                <!-- Clear notifications -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Clear Notification History</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Remove all stored notification records</p>
                    </div>
                    <button onclick="clearNotificationHistory()"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-all">
                        Clear All
                    </button>
                </div>

            </div>
        </div>

        <!-- ── Security ── -->
        <div id="tab-security" class="settings-content p-6">
            <div class="max-w-md space-y-5">
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                    <div class="font-semibold mb-1">Password Reset</div>
                    <p class="text-xs text-yellow-600 leading-relaxed">
                        To change your password, use the "Send Reset Link" option on your Profile page.
                        A password reset link will be sent to your registered email.
                    </p>
                </div>
                <a href="{{ route('profile') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-sm font-medium rounded-lg transition-all hover:opacity-90"
                   style="background-color: var(--primary-color);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Go to Profile
                </a>

                <hr class="border-gray-100">

                <!-- Session info -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Current Session</h3>
                    <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Account</span>
                            <span class="font-medium text-gray-800">{{ session('user.company_name', session('user.name', '-')) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Role</span>
                            <span class="font-medium text-gray-800">{{ session('user.role.name', 'Customer') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Customer Code</span>
                            <span class="font-mono text-xs text-gray-700">{{ session('user.customer_code', '-') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- end tab container -->
</div>
@endsection

@push('scripts')
<script>
/* ════════════════════════════════════════════
   Settings Page — localStorage-based preferences
   ════════════════════════════════════════════ */

var _PREFS_KEY = 'jarvies_preferences';
var _DEFAULT = {
    theme:                 'light',
    primary_color:         '#c62828',
    font_size:             'medium',
    compact_mode:          false,
    show_animations:       true,
    notifications_enabled: true,
};

// ── Current state ──
var _prefs = (function() {
    try {
        var s = localStorage.getItem(_PREFS_KEY);
        return s ? Object.assign({}, _DEFAULT, JSON.parse(s)) : Object.assign({}, _DEFAULT);
    } catch(e) { return Object.assign({}, _DEFAULT); }
})();

// ── Tab switching ──
function switchTab(name) {
    document.querySelectorAll('.settings-content').forEach(function(el) {
        el.classList.remove('active');
    });
    document.querySelectorAll('.settings-tab').forEach(function(btn) {
        btn.classList.remove('active');
        btn.classList.add('inactive');
    });
    var panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.add('active');
    var btn = document.querySelector('[data-tab="' + name + '"]');
    if (btn) { btn.classList.add('active'); btn.classList.remove('inactive'); }
}

// ── Theme selector ──
function selectTheme(theme) {
    _prefs.theme = theme;
    document.querySelectorAll('.theme-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    var el = document.getElementById('theme-' + theme);
    if (el) el.classList.add('selected');
}

// ── Color picker ──
function previewColor(hex) {
    _prefs.primary_color = hex;
    document.getElementById('colorHexLabel').textContent = hex;
    applyTheme(_prefs); // live preview via layout function
}

function setColorPreset(hex) {
    _prefs.primary_color = hex;
    var picker = document.getElementById('primaryColorPicker');
    if (picker) picker.value = hex;
    document.getElementById('colorHexLabel').textContent = hex;
    applyTheme(_prefs);
}

// ── Font size ──
function selectFontSize(size) {
    _prefs.font_size = size;
    document.querySelectorAll('.font-size-btn').forEach(function(btn) {
        var isActive = btn.getAttribute('data-fs') === size;
        btn.style.borderColor = isActive ? getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() : '';
        btn.style.color       = isActive ? getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() : '';
        btn.style.fontWeight  = isActive ? '600' : '';
    });
    applyTheme(_prefs);
}

// ── Toggle (compact mode, animations, notifications) ──
function togglePref(key, toggleId) {
    _prefs[key] = !_prefs[key];
    var track = document.getElementById(toggleId);
    if (track) track.classList.toggle('on', _prefs[key]);
    applyTheme(_prefs);
}

// ── Save ──
function saveSettings() {
    try {
        localStorage.setItem(_PREFS_KEY, JSON.stringify(_prefs));
        applyTheme(_prefs);
        showToast('Settings saved successfully!', 'success', 'Saved');
    } catch(e) {
        showToast('Could not save settings.', 'error', 'Error');
    }
}

// ── Reset ──
function resetSettings() {
    if (!confirm('Reset all settings to default?')) return;
    localStorage.removeItem(_PREFS_KEY);
    localStorage.removeItem('jarvies_bell_notifs');
    localStorage.removeItem('jarvies_ticket_states');
    _prefs = Object.assign({}, _DEFAULT);
    applyTheme(_prefs);
    showToast('Settings reset to default.', 'success', 'Reset');
    setTimeout(function() { location.reload(); }, 800);
}

// ── Clear notification history ──
function clearNotificationHistory() {
    localStorage.removeItem('jarvies_bell_notifs');
    localStorage.removeItem('jarvies_ticket_states');
    var badge = document.getElementById('bellBadge');
    if (badge) badge.classList.add('hidden');
    showToast('Notification history cleared.', 'success', 'Done');
}

// ── Init: apply current prefs to the form ──
document.addEventListener('DOMContentLoaded', function() {

    // Theme buttons
    selectTheme(_prefs.theme);

    // Color picker
    var picker = document.getElementById('primaryColorPicker');
    if (picker) picker.value = _prefs.primary_color;
    document.getElementById('colorHexLabel').textContent = _prefs.primary_color;

    // Font size
    selectFontSize(_prefs.font_size);

    // Toggles
    var toggleMap = {
        compact_mode:          'compactToggle',
        show_animations:       'animationsToggle',
        notifications_enabled: 'notifEnabledToggle',
    };
    Object.keys(toggleMap).forEach(function(key) {
        var el = document.getElementById(toggleMap[key]);
        if (el) el.classList.toggle('on', !!_prefs[key]);
    });
});
</script>
@endpush
