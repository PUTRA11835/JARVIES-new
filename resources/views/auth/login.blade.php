<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Jarvies Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(153, 27, 27, 0.3); }
            50% { box-shadow: 0 0 40px rgba(153, 27, 27, 0.5); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        .gradient-animated {
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
        }

        .logo-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        .input-field:focus {
            border-color: #991b1b;
            box-shadow: 0 0 0 3px rgba(153, 27, 27, 0.1);
        }

        /* Toast */
        #toast-container { position:fixed;top:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.75rem;max-width:22rem;width:100%;pointer-events:none; }
        .toast { display:flex;align-items:flex-start;gap:.75rem;padding:.875rem 1rem;border-radius:.75rem;border:1.5px solid #e5e7eb;box-shadow:0 4px 16px rgba(0,0,0,.08);position:relative;overflow:hidden;transform:translateX(110%);opacity:0;transition:transform .4s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;pointer-events:auto; }
        .toast.show { transform:translateX(0);opacity:1; }
        .toast-success { background:#f0fdf4;border-color:#86efac; }
        .toast-success .toast-icon { background:#dcfce7; }
        .toast-success .toast-icon svg { color:#16a34a; }
        .toast-success .toast-title { color:#14532d; }
        .toast-success .toast-message { color:#15803d; }
        .toast-success .toast-progress { background:#22c55e; }
        .toast-error { background:#fff1f1;border-color:#fca5a5; }
        .toast-error .toast-icon { background:#fee2e2; }
        .toast-error .toast-icon svg { color:#dc2626; }
        .toast-error .toast-title { color:#991b1b; }
        .toast-error .toast-message { color:#b91c1c; }
        .toast-error .toast-progress { background:#ef4444; }
        .toast-warning { background:#fffbeb;border-color:#fcd34d; }
        .toast-warning .toast-icon { background:#fef9c3; }
        .toast-warning .toast-icon svg { color:#d97706; }
        .toast-warning .toast-title { color:#78350f; }
        .toast-warning .toast-message { color:#92400e; }
        .toast-warning .toast-progress { background:#f59e0b; }
        .toast-icon { width:2.25rem;height:2.25rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .toast-body { flex:1;min-width:0; }
        .toast-title { font-size:.875rem;font-weight:600;line-height:1.25rem; }
        .toast-message { font-size:.8125rem;margin-top:.125rem;line-height:1.4; }
        .toast-close { flex-shrink:0;width:1.5rem;height:1.5rem;display:flex;align-items:center;justify-content:center;border-radius:.375rem;color:#9ca3af;font-size:1.1rem;cursor:pointer;transition:background .15s,color .15s;background:transparent;border:none;padding:0; }
        .toast-close:hover { background:rgba(0,0,0,.06);color:#374151; }
        .toast-progress { position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 .75rem .75rem;animation:toastProgressBar linear forwards; }
        @keyframes toastProgressBar { from{width:100%}to{width:0%} }
        /* Field error state */
        .input-field.field-error { border-color:#ef4444 !important; box-shadow:0 0 0 3px rgba(239,68,68,0.15) !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-gray-50 via-red-50 to-gray-100">

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Background Pattern -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none opacity-5">
        <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-gradient-to-br from-red-600 to-red-900 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-gradient-to-tr from-red-600 to-red-900 rounded-full blur-3xl"></div>
    </div>

    <main class="w-full max-w-5xl relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-5 bg-white rounded-3xl overflow-hidden shadow-2xl border border-gray-100">
            
            <!-- Left Side - Branding -->
            <section class="lg:col-span-2 bg-gradient-to-br from-red-800 via-red-900 to-red-950 gradient-animated text-white p-8 lg:p-12 flex flex-col justify-between relative overflow-hidden">
                
                <!-- Decorative Elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full -ml-24 -mb-24"></div>
                
                <!-- Floating Particles -->
                <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-red-400 rounded-full opacity-50 float-animation"></div>
                <div class="absolute top-1/3 right-1/4 w-3 h-3 bg-red-400 rounded-full opacity-40 float-animation" style="animation-delay: -2s;"></div>
                <div class="absolute bottom-1/3 left-1/3 w-2 h-2 bg-red-300 rounded-full opacity-60 float-animation" style="animation-delay: -4s;"></div>
                
                <div class="relative z-10">
                    <!-- Logo -->
                    <div class="mb-12">
                        <div class="text-center mb-8">
                            <div class="mx-auto flex items-center justify-center">
                                <div class="w-24 h-24 bg-white bg-opacity-10 rounded-3xl flex items-center justify-center logo-glow border border-white border-opacity-20">
                                    <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-bold leading-tight text-center">JARVIES</h1>
                        <p class="text-red-200 text-sm mt-2 font-medium text-center tracking-widest">PORTAL SYSTEM</p>
                    </div>

                    <!-- Features -->
                    <div class="space-y-4 mt-8">
                        @foreach(['Real-time Data Synchronization', 'Secure API Integration', 'Connected to EcoSystem'] as $feature)
                        <div class="flex items-center space-x-3 text-red-100">
                            <div class="w-8 h-8 bg-white bg-opacity-10 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span class="text-sm">{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Help Info -->
                <div class="relative z-10 mt-8">
                    <div class="p-4 bg-white bg-opacity-10 rounded-xl border border-white border-opacity-20 backdrop-blur-sm">
                        <div class="flex items-center space-x-3 mb-2">
                            <svg class="w-5 h-5 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-white">Need Assistance?</p>
                        </div>
                        <p class="text-sm text-red-200 leading-relaxed">Contact RPMO Team for any login issues or access requests.</p>
                    </div>
                </div>
            </section>

            <!-- Right Side - Login Form -->
            <section class="lg:col-span-3 p-8 lg:p-12 flex items-center justify-center bg-gray-50">
                <div class="w-full max-w-md">
                    
                    <!-- Mobile Logo -->
                    <div class="text-center mb-8 lg:hidden">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-800 to-red-900 rounded-2xl mb-4">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                        <p class="text-gray-600">Sign in to Jarvies Portal</p>
                    </div>

                    {{-- Flash messages via toast (rendered after JS loads) --}}
                    @if(session('success'))
                    <script>document.addEventListener('DOMContentLoaded',function(){showToast(@json(session('success')),'success','Sign Out',6000);});</script>
                    @endif
                    @if(session('error'))
                    <script>document.addEventListener('DOMContentLoaded',function(){showToast(@json(session('error')),'error','Authentication Error',7000);});</script>
                    @endif
                    @if($errors->any())
                    <script>document.addEventListener('DOMContentLoaded',function(){showToast(@json($errors->first()),'error','Validation Error',7000);});</script>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-5" autocomplete="on">
                        @csrf
                        
                        <!-- Identifier Field -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                Email / ECI / Phone
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Enter your identification"
                                    class="input-field w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-white"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    class="input-field w-full pl-12 pr-12 py-3.5 border-2 border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-white"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    type="button"
                                    onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    aria-label="Toggle password visibility"
                                >
                                    <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="eyeOffIcon" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full px-4 py-4 bg-gradient-to-r from-red-800 to-red-900 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-red-900/30 hover:shadow-xl hover:shadow-red-900/40 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center"
                        >
                            SIGN IN
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-gray-50 text-gray-500">Connected by EcoSystem</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center">
                        <p class="text-xs text-gray-500">© {{ date('Y') }} Jarvies Portal. All rights reserved.</p>
                    </div>

                </div>
            </section>

        </div>
    </main>

    <script>
        // ── Toast System ─────────────────────────────────────────────────────────
        var _icons = {
            success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
            error:   '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        };
        var _defaultTitles = { success: 'Success', error: 'Error', warning: 'Warning' };

        function showToast(message, type, title, duration, onClose) {
            type     = type     || 'error';
            duration = duration || 6000;
            var icon       = _icons[type]         || _icons.error;
            var toastTitle = title                 || _defaultTitles[type] || 'Notice';
            var container  = document.getElementById('toast-container');
            var toast      = document.createElement('div');
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
            requestAnimationFrame(function() { requestAnimationFrame(function() { toast.classList.add('show'); }); });
            toast._timer = setTimeout(function() { dismissToast(toast, onClose); }, duration);
            toast._onClose = onClose || null;
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

        // ── Field Helpers ─────────────────────────────────────────────────────────
        function setFieldError(id, hasError) {
            var el = document.getElementById(id);
            if (!el) return;
            if (hasError) {
                el.classList.add('field-error');
            } else {
                el.classList.remove('field-error');
            }
        }

        function clearFieldErrors() {
            setFieldError('email', false);
            setFieldError('password', false);
        }

        // Clear errors on typing
        document.getElementById('email')?.addEventListener('input', function() { setFieldError('email', false); });
        document.getElementById('password')?.addEventListener('input', function() { setFieldError('password', false); });

        // ── Toggle Password ───────────────────────────────────────────────────────
        function togglePassword() {
            var input     = document.getElementById('password');
            var eyeOn     = document.getElementById('eyeIcon');
            var eyeOff    = document.getElementById('eyeOffIcon');
            var isHidden  = input.type === 'password';
            input.type    = isHidden ? 'text' : 'password';
            eyeOn.classList.toggle('hidden', isHidden);
            eyeOff.classList.toggle('hidden', !isHidden);
        }

        // Enter key: email → password
        document.getElementById('email')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('password').focus(); }
        });

        // ── Client-Side Validation ────────────────────────────────────────────────
        function validateLoginForm(email, password) {
            clearFieldErrors();

            if (!email) {
                setFieldError('email', true);
                showToast('Please enter your email address, ECI number, or phone number to continue.', 'warning', 'Identifier Required');
                document.getElementById('email').focus();
                return false;
            }

            if (!password) {
                setFieldError('password', true);
                showToast('Please enter your password to continue.', 'warning', 'Password Required');
                document.getElementById('password').focus();
                return false;
            }

            if (password.length < 4) {
                setFieldError('password', true);
                showToast('Password must be at least 4 characters. Please check your input and try again.', 'warning', 'Password Too Short');
                document.getElementById('password').focus();
                return false;
            }

            return true;
        }

        // ── Server Error Parser ───────────────────────────────────────────────────
        function parseLoginError(status, data) {
            // 401 — wrong credentials (most common)
            if (status === 401) {
                setFieldError('email', true);
                setFieldError('password', true);
                var msg = data?.message || '';
                if (msg.toLowerCase().includes('not found') || msg.toLowerCase().includes('no account')) {
                    showToast('Check your email, ECI, or phone number and try again.', 'error', 'Account Not Found');
                } else if (msg.toLowerCase().includes('password') || msg.toLowerCase().includes('incorrect') || msg.toLowerCase().includes('invalid')) {
                    showToast('Username or Password Incorrect', 'error', 'Login Failed');
                } else if (msg.toLowerCase().includes('inactive') || msg.toLowerCase().includes('disabled') || msg.toLowerCase().includes('suspended')) {
                    setFieldError('email', false);
                    setFieldError('password', false);
                    showToast('Your account has been deactivated or suspended. Please contact the RPMO Team for assistance.', 'error', 'Account Inactive');
                } else {
                    showToast('The credentials you entered do not match our records. Please verify your identifier and password, then try again.', 'error', 'Authentication Failed');
                }
                return;
            }

            // 403 — account exists but access denied
            if (status === 403) {
                showToast('Your account does not have permission to access this portal. Please contact the RPMO Team to request access.', 'error', 'Access Denied');
                return;
            }

            // 422 — validation failed server-side
            if (status === 422) {
                var errors = data?.errors || {};
                if (errors.email) {
                    setFieldError('email', true);
                    showToast('The identifier field is required and must be a valid email, ECI number, or phone number.', 'warning', 'Invalid Identifier');
                } else if (errors.password) {
                    setFieldError('password', true);
                    showToast('The password field is required and must meet the minimum length requirement.', 'warning', 'Invalid Password');
                } else {
                    showToast('Some fields contain invalid data. Please review your input and try again.', 'warning', 'Validation Error');
                }
                return;
            }

            // 429 — too many attempts
            if (status === 429) {
                showToast('Too many failed login attempts. Your account has been temporarily locked for security. Please wait a few minutes before trying again.', 'warning', 'Too Many Attempts');
                return;
            }

            // 500+ — server errors
            if (status >= 500) {
                showToast('The server encountered an unexpected error while processing your request. Please try again in a few moments. If the problem persists, contact support.', 'error', 'Server Error');
                return;
            }

            // Generic fallback with server message
            var serverMsg = data?.message;
            if (serverMsg) {
                showToast(serverMsg, 'error', 'Login Failed');
            } else {
                showToast('An unexpected error occurred. Please try again or contact the RPMO Team if the issue continues.', 'error', 'Login Failed');
            }
        }

        // ── Form Submit ───────────────────────────────────────────────────────────
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();

            var form        = e.target;
            var submitBtn   = form.querySelector('button[type="submit"]');
            var email       = document.getElementById('email').value.trim();
            var password    = document.getElementById('password').value;
            var originalHTML = submitBtn.innerHTML;

            if (!validateLoginForm(email, password)) return;

            // Set loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<svg class="animate-spin h-5 w-5 mr-2 inline" fill="none" viewBox="0 0 24 24">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Signing in...';

            try {
                var formData = new URLSearchParams(new FormData(form));
                var response, data;

                try {
                    response = await fetch(form.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData,
                    });
                    data = await response.json();
                } catch (networkErr) {
                    showToast(
                        'Unable to connect to the server. Please check your internet connection and try again.',
                        'error', 'Connection Failed'
                    );
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                    return;
                }

                // Password change required
                if (data.success && data.require_password_change) {
                    showToast('Your account requires a password setup before you can sign in.', 'warning', 'Password Setup Required', 3000, function() {
                        window.location.href = '/password/check-email?email=' + encodeURIComponent(data.email ?? '') + '&type=setup';
                    });
                    return;
                }

                // Successful login
                if (data.success) {
                    showToast('Login successful. Redirecting to your dashboard...', 'success', 'Welcome Back!', 2500, function() {
                        window.location.href = '/dashboard';
                    });
                    return; // keep button disabled — redirecting
                }

                // Failed — parse server error and re-enable button
                parseLoginError(response.status, data);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;

            } catch (unexpectedErr) {
                console.error('Unexpected error:', unexpectedErr);
                showToast('An unexpected client-side error occurred. Please refresh the page and try again.', 'error', 'Unexpected Error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }
        });
    </script>

</body>
</html>