<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connect Email — Jarvies Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-gray-50 via-red-50 to-gray-100">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-800 to-red-950 rounded-2xl shadow-lg mb-4">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">One more step!</h1>
            <p class="text-gray-500 mt-2 text-sm leading-relaxed">
                Connect your email account so tickets can be sent<br>
                directly from your email to our support team.
            </p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">

            {{-- Flash success/error --}}
            @if(session('oauth_success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('oauth_success') }}
            </div>
            @endif
            @if(session('oauth_error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('oauth_error') }}
            </div>
            @endif

            <p class="text-xs text-gray-500 mb-4 text-center">Select the email provider you want to connect:</p>

            {{-- Google --}}
            <a href="{{ route('oauth.email.redirect', ['provider' => 'google', 'return' => route('dashboard')]) }}"
               class="flex items-center gap-3 w-full px-4 py-3.5 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all mb-3 group">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <div class="flex-1 text-left">
                    <div class="text-sm font-semibold text-gray-800 group-hover:text-gray-900">Continue with Google</div>
                    <div class="text-xs text-gray-400">Gmail & Google Account</div>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            {{-- Microsoft --}}
            <a href="{{ route('oauth.email.redirect', ['provider' => 'azure', 'return' => route('dashboard')]) }}"
               class="flex items-center gap-3 w-full px-4 py-3.5 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all mb-5 group">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                    <path d="M11.4 2H2v9.4h9.4V2z" fill="#F25022"/>
                    <path d="M22 2h-9.4v9.4H22V2z" fill="#7FBA00"/>
                    <path d="M11.4 12.6H2V22h9.4v-9.4z" fill="#00A4EF"/>
                    <path d="M22 12.6h-9.4V22H22v-9.4z" fill="#FFB900"/>
                </svg>
                <div class="flex-1 text-left">
                    <div class="text-sm font-semibold text-gray-800 group-hover:text-gray-900">Continue with Microsoft</div>
                    <div class="text-xs text-gray-400">Outlook & Microsoft Account</div>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <div class="relative mb-5">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                <div class="relative flex justify-center">
                    <span class="px-3 bg-white text-xs text-gray-400">or</span>
                </div>
            </div>

            {{-- Skip --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center justify-center w-full px-4 py-3 text-sm text-gray-500 hover:text-gray-700 rounded-xl hover:bg-gray-50 transition-colors border border-gray-200 hover:border-gray-300">
                Skip, I'll do this later
            </a>

            <p class="text-xs text-gray-400 text-center mt-4 leading-relaxed">
                One-time setup. Your token is stored securely and only used for sending tickets.
                You can still create tickets without connecting an email.
            </p>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-400 mt-6">
            JARVIES Portal &mdash; PT Eclectic Consulting Yogyakarta
        </p>

    </div>
</body>
</html>
