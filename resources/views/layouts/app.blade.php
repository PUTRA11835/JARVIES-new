<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Jarvies Portal</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #c4c4c4;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-right: 3px solid white;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="@yield('sidebar-width', 'w-16 lg:w-64') bg-gradient-to-b from-red-800 via-red-900 to-red-950 text-white flex flex-col flex-shrink-0">

            @hasSection('sidebar-nav')
                {{-- Custom sidebar content (e.g. ticket list on show page) --}}
                @yield('sidebar-nav')
            @else
                {{-- Default: Logo + Navigation + User Info --}}

                <!-- Logo -->
                <div class="p-4 flex items-center justify-center lg:justify-start space-x-3 border-b border-red-700/50">
                    <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="hidden lg:block">
                        <h1 class="font-bold text-lg">JARVIES</h1>
                        <p class="text-red-300 text-xs">Portal System</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 py-4 overflow-y-auto">
                    <ul class="space-y-1 px-2">
                        <li>
                            <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard*') ? 'active' : '' }} flex items-center justify-center lg:justify-start space-x-3 px-3 py-3 rounded-lg">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span class="hidden lg:block">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tickets.index') }}" class="sidebar-item {{ request()->routeIs('tickets*') ? 'active' : '' }} flex items-center justify-center lg:justify-start space-x-3 px-3 py-3 rounded-lg text-red-200 hover:text-white">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                <span class="hidden lg:block">Tickets</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- User Info -->
                <div class="p-4 border-t border-red-700/50">
                    <div class="flex items-center justify-center lg:justify-start space-x-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold">{{ strtoupper(substr(session('user.name', 'U'), 0, 1)) }}</span>
                        </div>
                        <div class="hidden lg:block flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ session('user.name', 'User') }}</p>
                            <p class="text-xs text-red-300 truncate">{{ session('user.role.name', 'Role') }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="hidden lg:block">
                            @csrf
                            <button type="submit" class="p-2 hover:bg-white/10 rounded-lg transition-colors" title="Logout">
                                <svg class="w-4 h-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

            @endif
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">@yield('page-title', 'Page')</h1>
                        <p class="text-sm text-gray-500">@yield('page-subtitle', '')</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        @yield('header-actions')

                        <!-- Profile -->
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <span class="text-red-800 text-sm font-semibold">{{ strtoupper(substr(session('user.name', 'U'), 0, 1)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6">
                
                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
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

    @stack('scripts')
</body>
</html>