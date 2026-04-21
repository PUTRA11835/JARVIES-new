<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - JARVIES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .input-field:focus {
            border-color: #991b1b;
            box-shadow: 0 0 0 3px rgba(153, 27, 27, 0.1);
        }
        #toast-container { position:fixed;top:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.75rem;max-width:22rem;width:100%;pointer-events:none; }
        .toast { display:flex;align-items:flex-start;gap:.75rem;padding:.875rem 1rem;border-radius:.75rem;border:1.5px solid #e5e7eb;box-shadow:0 4px 16px rgba(0,0,0,.08);position:relative;overflow:hidden;transform:translateX(110%);opacity:0;transition:transform .4s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;pointer-events:auto; }
        .toast.show { transform:translateX(0);opacity:1; }
        .toast-success { background:#f0fdf4;border-color:#86efac; } .toast-success .toast-icon{background:#dcfce7;} .toast-success .toast-icon svg{color:#16a34a;} .toast-success .toast-title{color:#14532d;} .toast-success .toast-message{color:#15803d;} .toast-success .toast-progress{background:#22c55e;}
        .toast-error { background:#fff1f1;border-color:#fca5a5; } .toast-error .toast-icon{background:#fee2e2;} .toast-error .toast-icon svg{color:#dc2626;} .toast-error .toast-title{color:#991b1b;} .toast-error .toast-message{color:#b91c1c;} .toast-error .toast-progress{background:#ef4444;}
        .toast-icon { width:2.25rem;height:2.25rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .toast-body { flex:1;min-width:0; } .toast-title { font-size:.875rem;font-weight:600;line-height:1.25rem; } .toast-message { font-size:.8125rem;margin-top:.125rem;line-height:1.4; }
        .toast-close { flex-shrink:0;width:1.5rem;height:1.5rem;display:flex;align-items:center;justify-content:center;border-radius:.375rem;color:#9ca3af;font-size:1.1rem;cursor:pointer;transition:background .15s,color .15s;background:transparent;border:none;padding:0; }
        .toast-progress { position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 .75rem .75rem;animation:toastProgressBar linear forwards; }
        @keyframes toastProgressBar { from{width:100%}to{width:0%} }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-gray-50 via-red-50 to-gray-100">

    <div id="toast-container"></div>

    <main class="w-full max-w-md">
        <div class="bg-white rounded-3xl overflow-hidden shadow-2xl border border-gray-100 p-10">

            <!-- Logo -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-800 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-gray-900 tracking-wide">JARVIES</span>
                </div>
            </div>

            <!-- Icon -->
            <div class="mx-auto mb-4 w-16 h-16 bg-red-50 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 text-center mb-1">Forgot Password?</h1>
            <p class="text-gray-500 text-sm text-center mb-8 leading-relaxed">
                Enter the email registered to your account.<br>
                We will send you a link to reset your password.
            </p>

            {{-- Flash messages via toast --}}
            @if(session('success'))
            <script>document.addEventListener('DOMContentLoaded',function(){showToast(@json(session('success')),'success');});</script>
            @endif
            @if(session('error'))
            <script>document.addEventListener('DOMContentLoaded',function(){showToast(@json(session('error')),'error');});</script>
            @endif

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password-setup.forgot.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Registered Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="name@company.com"
                            class="input-field w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-white"
                            required
                            autofocus
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-4 bg-gradient-to-r from-red-800 to-red-900 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-red-900/30 hover:shadow-xl hover:shadow-red-900/40 hover:-translate-y-0.5 active:translate-y-0"
                >
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Password Reset Link
                    </span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center text-sm text-red-800 font-semibold hover:text-red-900 hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to login
                </a>
            </div>

        </div>
    </main>

    <script>
    function showToast(message, type, title, duration) {
        type = type || 'info'; duration = duration || 5000;
        var icons = { success:'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>', error:'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>' };
        var titles = { success:'Success', error:'An Error Occurred' };
        var container = document.getElementById('toast-container');
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<div class="toast-icon">'+(icons[type]||icons.error)+'</div><div class="toast-body"><p class="toast-title">'+(title||titles[type]||'Info')+'</p><p class="toast-message">'+message+'</p></div><button class="toast-close" onclick="this.parentElement.classList.remove(\'show\')">&times;</button><div class="toast-progress" style="animation-duration:'+duration+'ms"></div>';
        container.appendChild(toast);
        requestAnimationFrame(function(){requestAnimationFrame(function(){toast.classList.add('show');});});
        setTimeout(function(){toast.classList.remove('show');setTimeout(function(){if(toast.parentElement)toast.remove();},350);},duration);
    }
    </script>

</body>
</html>
