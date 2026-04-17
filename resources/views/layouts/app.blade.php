<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Jarvies Portal</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── CSS Custom Properties (Theming) ── */
        :root {
            --primary-color: #c62828;
            --primary-rgb: 198, 40, 40;
            --primary-dark: #991b1b;
            --bg-color: #f9fafb;
            --text-color: #111827;
            --card-bg: #ffffff;
            --base-font-size: 14px;
        }

        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        html { font-size: var(--base-font-size); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 10px; border: 2px solid #f1f1f1; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }

        /* Sidebar nav link hover & active */
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { transform: translateX(4px); }
        .nav-link.active { box-shadow: 0 4px 12px rgba(var(--primary-rgb),0.3); }

        /* ── Toast System ── */
        #toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-width: 22rem;
            width: 100%;
            pointer-events: none;
        }
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            transform: translateX(110%);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
            pointer-events: auto;
        }
        .toast.show { transform: translateX(0); opacity: 1; }

        .toast-success { background: #f0fdf4; border-color: #86efac; }
        .toast-success .toast-icon { background: #dcfce7; }
        .toast-success .toast-icon svg { color: #16a34a; }
        .toast-success .toast-title { color: #14532d; }
        .toast-success .toast-message { color: #15803d; }
        .toast-success .toast-progress { background: #22c55e; }

        .toast-error { background: #fff1f1; border-color: #fca5a5; }
        .toast-error .toast-icon { background: #fee2e2; }
        .toast-error .toast-icon svg { color: #dc2626; }
        .toast-error .toast-title { color: #991b1b; }
        .toast-error .toast-message { color: #b91c1c; }
        .toast-error .toast-progress { background: #ef4444; }

        .toast-warning { background: #fffbeb; border-color: #fcd34d; }
        .toast-warning .toast-icon { background: #fef9c3; }
        .toast-warning .toast-icon svg { color: #d97706; }
        .toast-warning .toast-title { color: #78350f; }
        .toast-warning .toast-message { color: #92400e; }
        .toast-warning .toast-progress { background: #f59e0b; }

        .toast-info { background: #eff6ff; border-color: #93c5fd; }
        .toast-info .toast-icon { background: #dbeafe; }
        .toast-info .toast-icon svg { color: #2563eb; }
        .toast-info .toast-title { color: #1e3a8a; }
        .toast-info .toast-message { color: #1d4ed8; }
        .toast-info .toast-progress { background: #3b82f6; }

        .toast-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .toast-body { flex: 1; min-width: 0; }
        .toast-title { font-size: 0.875rem; font-weight: 600; line-height: 1.25rem; }
        .toast-message { font-size: 0.8125rem; margin-top: 0.125rem; line-height: 1.4; }
        .toast-close {
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            color: #9ca3af;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            background: transparent;
            border: none;
            padding: 0;
        }
        .toast-close:hover { background: rgba(0,0,0,0.06); color: #374151; }
        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 0.75rem 0.75rem;
            animation: toastProgressBar linear forwards;
        }
        @keyframes toastProgressBar { from { width: 100%; } to { width: 0%; } }

        /* ── Notification Bell Dropdown ── */
        #bellDropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 22rem;
            background: #fff;
            border-radius: 0.875rem;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            z-index: 60;
        }
        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
            color: inherit;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: #f9fafb; }
        .notif-item.unread { background: #fef2f2; }
        .notif-item.unread:hover { background: #fee2e2; }
        .notif-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-color);
            flex-shrink: 0;
            margin-top: 6px;
        }
        .notif-item.read .notif-dot { background: #d1d5db; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
<div class="flex min-h-screen">

    <!-- ══════════════════ SIDEBAR (Fixed) ══════════════════ -->
    <aside id="sidebar"
           class="fixed h-screen flex flex-col bg-gradient-to-b from-red-800 via-red-900 to-red-950 text-white shadow-2xl z-50 w-64 transition-all duration-300">

        <!-- Logo Section -->
        <div class="p-5 pb-4 flex items-center justify-center border-b border-red-700/50 shrink-0">
            <div class="w-full">
                {{-- Expanded Logo --}}
                <div class="logo-expanded flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-none">JARVIES</h1>
                        <p class="text-red-300 text-xs mt-0.5">Portal System</p>
                    </div>
                </div>
                {{-- Collapsed Logo --}}
                <div class="logo-collapsed hidden justify-center">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation / Custom Sidebar Content -->
        @hasSection('sidebar-nav')
            @yield('sidebar-nav')
        @else
        <nav class="flex-1 py-6 px-4 overflow-y-auto">
            <!-- Dashboard -->
            <div class="mb-2">
                <a href="{{ route('dashboard') }}"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard*') ? 'active bg-white/20 font-semibold' : 'text-white/80 hover:bg-white/10' }} transition-all">
                    <span class="w-5 h-5 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </span>
                    <span class="nav-text font-medium">Dashboard</span>
                </a>
            </div>
            <!-- Tickets -->
            <div class="mb-2">
                <a href="{{ route('tickets.index') }}"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('tickets*') ? 'active bg-white/20 font-semibold' : 'text-white/80 hover:bg-white/10' }} transition-all">
                    <span class="w-5 h-5 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </span>
                    <span class="nav-text font-medium">Tickets</span>
                </a>
            </div>
            <!-- Settings -->
            <div class="mb-2">
                <a href="{{ route('settings') }}"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('settings*') ? 'active bg-white/20 font-semibold' : 'text-white/80 hover:bg-white/10' }} transition-all">
                    <span class="w-5 h-5 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <span class="nav-text font-medium">Settings</span>
                </a>
            </div>
        </nav>
        @endif

    </aside>

    <!-- ══════════════════ MAIN CONTENT ══════════════════ -->
    <main id="mainContent" class="flex-1 ml-64 transition-all duration-300 overflow-y-auto flex flex-col min-h-screen">

        <!-- Header / Navbar -->
        <header class="bg-white sticky top-0 z-40 shadow-sm border-b border-gray-100 shrink-0">
            <div class="px-6 py-4 flex items-center justify-between">

                <!-- Left: Hamburger + Page Title -->
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()"
                            class="w-10 h-10 flex items-center justify-center border-2 border-red-200 rounded-xl hover:border-red-800 hover:bg-red-50 text-red-800 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-xs text-gray-500">@yield('page-subtitle', '')</p>
                    </div>
                </div>

                <!-- Right: Header Actions + Notifications + User Dropdown -->
                <div class="flex items-center gap-3">

                    @yield('header-actions')

                    <!-- ── Notification Bell ── -->
                    <div class="relative" id="bellWrapper">
                        <button id="bellBtn" onclick="toggleBellDropdown()"
                                class="relative w-10 h-10 flex items-center justify-center border-2 border-gray-200 rounded-xl hover:border-red-800 hover:bg-red-50 transition-all text-gray-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="bellBadge"
                                  class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white leading-none">
                                0
                            </span>
                        </button>

                        <!-- Bell Dropdown -->
                        <div id="bellDropdown" class="hidden">
                            <!-- Header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900 text-sm">Notifications</h3>
                                <button onclick="markAllBellRead()" class="text-xs font-medium hover:underline" style="color: var(--primary-color);">
                                    Mark all read
                                </button>
                            </div>

                            <!-- Notification List -->
                            <div id="bellNotifList" class="overflow-y-auto" style="max-height: 340px;">
                                <div class="text-center py-10 text-gray-400 text-sm">
                                    <svg class="mx-auto w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    No notifications
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="border-t border-gray-100 px-4 py-2.5 text-center">
                                <a href="{{ route('tickets.index') }}" class="text-xs font-medium hover:underline" style="color: var(--primary-color);">
                                    View all tickets →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <button onclick="toggleUserDropdown()"
                                class="flex items-center gap-3 px-4 py-2.5 border-2 border-gray-200 rounded-xl hover:bg-gray-50 hover:border-red-800 transition-all">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-700 to-red-900 text-white flex items-center justify-center font-bold text-sm shadow-md shrink-0">
                                {{ strtoupper(substr(session('user.name', session('user.company_name', 'U')), 0, 2)) }}
                            </div>
                            <div class="text-left hidden xl:block">
                                <div class="text-sm font-bold text-gray-900 leading-none mb-0.5">
                                    {{ Str::limit(session('user.name', session('user.company_name', 'User')), 18) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ Str::limit(session('user.company_name', 'Jarvies'), 22) }}</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userDropdown"
                             class="hidden absolute top-full right-0 mt-2 w-60 bg-white rounded-xl shadow-2xl border-2 border-gray-100 p-2 z-50">
                            <!-- User Info Header -->
                            <div class="px-4 py-3 border-b border-gray-100 mb-1">
                                <p class="text-sm font-bold text-gray-900 truncate">
                                    {{ session('user.name', session('user.company_name', 'User')) }}
                                </p>
                                <p class="text-xs text-gray-400 truncate">{{ session('user.company_name', 'Jarvies') }}</p>
                            </div>
                            <!-- Profile -->
                            <a href="{{ route('profile') }}"
                               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm w-full text-left transition-all font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>My Profile</span>
                            </a>
                            <!-- Settings -->
                            <a href="{{ route('settings') }}"
                               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm w-full text-left transition-all font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Settings</span>
                            </a>
                            <!-- Sign Out -->
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 text-sm w-full text-left transition-all font-medium">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="flex-1 p-8">
            @yield('content')
        </div>

    </main>

</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
    /* ── Toast System ── */
    var _toastIcons = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        error:   '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
        warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        info:    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };
    var _toastTitles = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Information' };

    function showToast(message, type, title, duration, onClose) {
        type     = type     || 'info';
        duration = duration || 5000;
        var toastTitle = title || _toastTitles[type] || 'Informasi';
        var icon       = _toastIcons[type] || _toastIcons.info;

        var container = document.getElementById('toast-container');
        var toast     = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML =
            '<div class="toast-icon">' + icon + '</div>' +
            '<div class="toast-body">' +
                '<p class="toast-title">' + toastTitle + '</p>' +
                '<p class="toast-message">' + message + '</p>' +
            '</div>' +
            '<button class="toast-close" onclick="dismissToast(this.parentElement)">&times;</button>' +
            '<div class="toast-progress" style="animation-duration:' + duration + 'ms"></div>';

        container.appendChild(toast);
        requestAnimationFrame(function() {
            requestAnimationFrame(function() { toast.classList.add('show'); });
        });

        toast._timer   = setTimeout(function() { dismissToast(toast, onClose); }, duration);
        toast._onClose = onClose || null;
        return toast;
    }

    function dismissToast(toast, onClose) {
        if (!toast || !toast.parentElement) return;
        clearTimeout(toast._timer);
        toast.classList.remove('show');
        var cb = onClose || toast._onClose;
        setTimeout(function() {
            if (toast.parentElement) toast.remove();
            if (typeof cb === 'function') cb();
        }, 350);
    }

    /* ── Auto-show flash messages as toast ── */
    @if(session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('success')), 'success');
    });
    @endif
    @if(session('error'))
    document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('error')), 'error');
    });
    @endif
    @if(session('warning'))
    document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('warning')), 'warning');
    });
    @endif
    @if(session('info'))
    document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('info')), 'info');
    });
    @endif

    /* ── Theming / Preferences ── */
    var _DEFAULT_PREFS = {
        theme:                 'light',
        primary_color:         '#c62828',
        font_size:             'medium',
        compact_mode:          false,
        show_animations:       true,
        notifications_enabled: true,
    };

    function _loadPrefs() {
        try {
            var saved = localStorage.getItem('jarvies_preferences');
            return saved ? Object.assign({}, _DEFAULT_PREFS, JSON.parse(saved)) : Object.assign({}, _DEFAULT_PREFS);
        } catch(e) { return Object.assign({}, _DEFAULT_PREFS); }
    }

    function _hexToRgb(hex) {
        var r = parseInt(hex.slice(1,3), 16);
        var g = parseInt(hex.slice(3,5), 16);
        var b = parseInt(hex.slice(5,7), 16);
        return r + ', ' + g + ', ' + b;
    }

    function _darkenHex(hex) {
        var r = Math.max(0, parseInt(hex.slice(1,3), 16) - 30);
        var g = Math.max(0, parseInt(hex.slice(3,5), 16) - 30);
        var b = Math.max(0, parseInt(hex.slice(5,7), 16) - 30);
        return r + ', ' + g + ', ' + b;
    }

    function applyTheme(prefs) {
        var root = document.documentElement;

        // Primary color
        if (prefs.primary_color) {
            root.style.setProperty('--primary-color', prefs.primary_color);
            root.style.setProperty('--primary-rgb',   _hexToRgb(prefs.primary_color));
            root.style.setProperty('--primary-dark',  '#' + Math.max(0, parseInt(prefs.primary_color.slice(1,3),16)-40).toString(16).padStart(2,'0')
                                                            + Math.max(0, parseInt(prefs.primary_color.slice(3,5),16)-40).toString(16).padStart(2,'0')
                                                            + Math.max(0, parseInt(prefs.primary_color.slice(5,7),16)-40).toString(16).padStart(2,'0'));
        }

        // Font size
        var fontMap = { small: '12px', medium: '14px', large: '16px' };
        root.style.setProperty('--base-font-size', fontMap[prefs.font_size] || '14px');

        // Compact mode
        if (prefs.compact_mode) {
            document.body.classList.add('compact-mode');
        } else {
            document.body.classList.remove('compact-mode');
        }

        // Animations
        if (prefs.show_animations === false) {
            root.style.setProperty('--transition-speed', '0ms');
        } else {
            root.style.removeProperty('--transition-speed');
        }

        // Dark mode (simple class toggle — expand as needed)
        if (prefs.theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else if (prefs.theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }

    // Apply theme on page load
    (function() { applyTheme(_loadPrefs()); })();

    /* ── Sidebar Toggle ── */
    var isCollapsed = false;

    function toggleSidebar() {
        var sidebar     = document.getElementById('sidebar');
        var mainContent = document.getElementById('mainContent');
        var navTexts    = document.querySelectorAll('.nav-text');
        var logoExp     = document.querySelector('.logo-expanded');
        var logoColl    = document.querySelector('.logo-collapsed');

        isCollapsed = !isCollapsed;

        if (isCollapsed) {
            sidebar.classList.replace('w-64', 'w-16');
            mainContent.classList.replace('ml-64', 'ml-16');
            navTexts.forEach(function(t) { t.classList.add('hidden'); });
            if (logoExp)  logoExp.classList.add('hidden');
            if (logoColl) { logoColl.classList.remove('hidden'); logoColl.classList.add('flex'); }
            document.querySelectorAll('.nav-link').forEach(function(l) {
                l.classList.add('justify-center');
                l.classList.remove('gap-3');
            });
        } else {
            sidebar.classList.replace('w-16', 'w-64');
            mainContent.classList.replace('ml-16', 'ml-64');
            navTexts.forEach(function(t) { t.classList.remove('hidden'); });
            if (logoExp)  logoExp.classList.remove('hidden');
            if (logoColl) { logoColl.classList.add('hidden'); logoColl.classList.remove('flex'); }
            document.querySelectorAll('.nav-link').forEach(function(l) {
                l.classList.remove('justify-center');
                l.classList.add('gap-3');
            });
        }
    }

    /* ── User Dropdown ── */
    function toggleUserDropdown() {
        document.getElementById('userDropdown').classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        // Close user dropdown when clicking outside
        var userBtn      = e.target.closest('button[onclick="toggleUserDropdown()"]');
        var userDropdown = document.getElementById('userDropdown');
        if (!userBtn && userDropdown && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
        // Close bell dropdown when clicking outside
        var bellWrapper = document.getElementById('bellWrapper');
        if (bellWrapper && !bellWrapper.contains(e.target)) {
            var bd = document.getElementById('bellDropdown');
            if (bd) bd.classList.add('hidden');
        }
    });

    /* ── Notification Bell ── */
    var _bellNotifications = []; // [{ ticket_id, ticket_number, subject, updated_at, read }]
    var _ticketStates = {};       // { ticket_id: updated_at_string }
    var _bellInitialized = false;

    function _getNotifPrefs() {
        return _loadPrefs().notifications_enabled !== false;
    }

    function _saveBellState() {
        try { localStorage.setItem('jarvies_bell_notifs', JSON.stringify(_bellNotifications)); } catch(e) {}
        try { localStorage.setItem('jarvies_ticket_states', JSON.stringify(_ticketStates)); } catch(e) {}
    }

    function _loadBellState() {
        try {
            var n = localStorage.getItem('jarvies_bell_notifs');
            if (n) _bellNotifications = JSON.parse(n);
        } catch(e) {}
        try {
            var s = localStorage.getItem('jarvies_ticket_states');
            if (s) _ticketStates = JSON.parse(s);
        } catch(e) {}
    }

    function _updateBellBadge() {
        var badge = document.getElementById('bellBadge');
        if (!badge) return;
        var unread = _bellNotifications.filter(function(n) { return !n.read; }).length;
        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : unread;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function _renderBellList() {
        var list = document.getElementById('bellNotifList');
        if (!list) return;
        if (_bellNotifications.length === 0) {
            list.innerHTML =
                '<div class="text-center py-10 text-gray-400 text-sm">' +
                '<svg class="mx-auto w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' +
                'No notifications</div>';
            return;
        }
        var html = '';
        var shown = _bellNotifications.slice(0, 8);
        shown.forEach(function(n) {
            var readClass = n.read ? 'read' : 'unread';
            var timeAgo   = _timeAgo(n.updated_at);
            var ticketUrl = '/tickets/' + n.ticket_id;
            html +=
                '<a href="' + ticketUrl + '" class="notif-item ' + readClass + '" onclick="markNotifRead(' + n.ticket_id + ')">' +
                    '<span class="notif-dot mt-1 flex-shrink-0"></span>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-semibold text-gray-800 truncate">' + _escHtml(n.subject || 'Ticket #' + n.ticket_number) + '</p>' +
                        '<p class="text-xs text-gray-500 mt-0.5">Ticket updated &bull; ' + timeAgo + '</p>' +
                    '</div>' +
                '</a>';
        });
        list.innerHTML = html;
    }

    function _escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function _timeAgo(isoStr) {
        if (!isoStr) return '';
        var diff = (Date.now() - new Date(isoStr).getTime()) / 1000;
        if (diff < 60)   return 'just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function toggleBellDropdown() {
        var bd = document.getElementById('bellDropdown');
        if (!bd) return;
        var willOpen = bd.classList.contains('hidden');
        bd.classList.toggle('hidden');
        if (willOpen) {
            _renderBellList();
        }
    }

    function markNotifRead(ticketId) {
        _bellNotifications.forEach(function(n) {
            if (n.ticket_id == ticketId) n.read = true;
        });
        _saveBellState();
        _updateBellBadge();
    }

    function markAllBellRead() {
        _bellNotifications.forEach(function(n) { n.read = true; });
        _saveBellState();
        _updateBellBadge();
        _renderBellList();
    }

    function _pollTickets() {
        if (!_getNotifPrefs()) return;
        fetch('/tickets/ajax/fetch', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !Array.isArray(data.data)) return;
            var tickets = data.data.filter(function(t) { return !t.is_staging && t.ticket_id; });

            if (!_bellInitialized) {
                // First load: populate states without triggering notifications
                tickets.forEach(function(t) {
                    _ticketStates[t.ticket_id] = t.updated_at;
                });
                _bellInitialized = true;
                _saveBellState();
                _updateBellBadge();
                return;
            }

            var hasNew = false;
            tickets.forEach(function(t) {
                var prev = _ticketStates[t.ticket_id];
                var curr = t.updated_at;
                if (prev === undefined) {
                    // Brand new ticket — no notification (customer submitted it)
                    _ticketStates[t.ticket_id] = curr;
                    return;
                }
                if (prev !== curr) {
                    // Ticket was updated since last check
                    _ticketStates[t.ticket_id] = curr;
                    // Check if already in list
                    var existing = _bellNotifications.find(function(n) { return n.ticket_id == t.ticket_id; });
                    var notifObj = {
                        ticket_id:     t.ticket_id,
                        ticket_number: t.ticket_number || t.ticket_id,
                        subject:       t.subject || t.description || ('Ticket #' + (t.ticket_number || t.ticket_id)),
                        updated_at:    curr,
                        read:          false,
                    };
                    if (existing) {
                        // Update and mark unread
                        Object.assign(existing, notifObj);
                    } else {
                        _bellNotifications.unshift(notifObj);
                        // Show toast notification
                        showToast(
                            'Ticket #' + (t.ticket_number || t.ticket_id) + ' has been updated.',
                            'info',
                            'Ticket Update'
                        );
                    }
                    // Keep max 20
                    if (_bellNotifications.length > 20) _bellNotifications.pop();
                    hasNew = true;
                }
            });

            if (hasNew) {
                _saveBellState();
                _updateBellBadge();
                // Re-render if dropdown is open
                var bd = document.getElementById('bellDropdown');
                if (bd && !bd.classList.contains('hidden')) _renderBellList();
            }
        })
        .catch(function() {}); // Silently ignore network errors
    }

    // Initialize bell on page load
    document.addEventListener('DOMContentLoaded', function() {
        _loadBellState();
        _updateBellBadge();
        // Start polling — first call initializes states
        _pollTickets();
        setInterval(_pollTickets, 30000);
    });
</script>

@stack('scripts')
</body>
</html>
