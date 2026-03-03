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
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #b91c1c; border-radius: 10px; border: 2px solid #f1f1f1; }
        ::-webkit-scrollbar-thumb:hover { background: #991b1b; }

        /* Sidebar nav link hover & active */
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { transform: translateX(4px); }
        .nav-link.active { box-shadow: 0 4px 12px rgba(153,27,27,0.3); }
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

                    <!-- Notification Bell -->
                    <button class="relative w-10 h-10 flex items-center justify-center border-2 border-gray-200 rounded-xl hover:border-red-800 hover:bg-red-50 transition-all text-gray-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-red-600 rounded-full border-2 border-white"></span>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <button onclick="toggleUserDropdown()"
                                class="flex items-center gap-3 px-4 py-2.5 border-2 border-gray-200 rounded-xl hover:bg-gray-50 hover:border-red-800 transition-all">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-700 to-red-900 text-white flex items-center justify-center font-bold text-sm shadow-md shrink-0">
                                {{ strtoupper(substr(session('user.company_name', session('user.name', 'U')), 0, 2)) }}
                            </div>
                            <div class="text-left hidden xl:block">
                                <div class="text-sm font-bold text-gray-900 leading-none mb-0.5">
                                    {{ Str::limit(session('user.company_name', session('user.name', 'User')), 18) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ session('user.role.name', 'Customer') }}</div>
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
                                    {{ session('user.company_name', session('user.name', 'User')) }}
                                </p>
                                <p class="text-xs text-gray-400">{{ session('user.role.name', 'Customer') }}</p>
                            </div>
                            <!-- Profile -->
                            <a href="{{ route('profile') }}"
                               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 text-gray-700 text-sm w-full text-left transition-all font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>My Profile</span>
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

            {{-- Flash: Success --}}
            @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
            @endif

            {{-- Flash: Error --}}
            @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            @yield('content')
        </div>

    </main>

</div>

<script>
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

    function toggleUserDropdown() {
        document.getElementById('userDropdown').classList.toggle('hidden');
    }

    // Close user dropdown when clicking outside
    document.addEventListener('click', function(e) {
        var btn      = e.target.closest('button[onclick="toggleUserDropdown()"]');
        var dropdown = document.getElementById('userDropdown');
        if (!btn && dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>

@stack('scripts')
</body>
</html>
