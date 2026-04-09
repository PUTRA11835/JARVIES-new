<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Statement — JARVIES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.5rem 2rem; margin-bottom: 1.25rem; }
        .section-card:hover { border-color: #bfdbfe; box-shadow: 0 4px 16px rgba(59,130,246,0.07); transition: all 0.2s; }
        .icon-box { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        h2 { font-size: 1rem; font-weight: 700; color: #111827; margin-bottom: 0.75rem; }
        p  { color: #4b5563; line-height: 1.75; font-size: 0.9375rem; margin-bottom: 0.75rem; }
        ul { padding-left: 0; list-style: none; margin-bottom: 0.75rem; }
        ul li { color: #4b5563; line-height: 1.75; font-size: 0.9375rem; padding-left: 1.25rem; position: relative; margin-bottom: 0.25rem; }
        ul li::before { content: "•"; color: #1d4ed8; font-weight: bold; position: absolute; left: 0; }
        a { color: #991b1b; text-decoration: underline; }
        a:hover { color: #7f1d1d; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 0.75rem; font-size: 0.875rem; }
        th { background: #f9fafb; text-align: left; padding: 10px 14px; font-weight: 600; color: #374151; border: 1px solid #e5e7eb; }
        td { padding: 10px 14px; color: #4b5563; border: 1px solid #e5e7eb; vertical-align: top; line-height: 1.6; }
        tr:hover td { background: #f9fafb; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    {{-- Header --}}
    <header class="bg-linear-to-r from-red-900 to-red-700 text-white shadow-lg">
        <div class="max-w-4xl mx-auto px-6 py-3.5 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                {{-- Eclectic Consulting Logo --}}
                <div class="bg-white rounded-xl px-3 py-1.5 shadow-sm group-hover:shadow-md transition-shadow shrink-0 flex items-center">
                    <img src="{{ asset('images/eclectic_logo_nobg.png') }}"
                         alt="PT Eclectic Consulting"
                         class="h-9 w-auto object-contain max-w-[120px]">
                </div>
                {{-- Divider --}}
                <div class="w-px h-8 bg-white/25 shrink-0"></div>
                {{-- Brand Text --}}
                <div class="min-w-0">
                    <div class="font-bold text-sm leading-tight tracking-wide">JARVIES</div>
                    <div class="text-white/60 text-xs">PT Eclectic Consulting</div>
                </div>
            </a>
            <a href="{{ route('legal.terms') }}"
               class="text-white/70 hover:text-white text-sm font-medium transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Terms of Service
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <div class="bg-linear-to-b from-red-900/5 to-transparent border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-6 py-10">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center shrink-0 mt-1">
                    <svg class="w-7 h-7 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Privacy Statement</h1>
                    <p class="text-gray-500 text-sm mt-1">Last updated: April 2, 2026 &nbsp;·&nbsp; PT Eclectic Consulting</p>
                    <div class="mt-3 inline-flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-xs font-medium px-3 py-1.5 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        We are committed to protecting your personal data
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <main class="max-w-4xl mx-auto px-6 py-10 space-y-3">

        {{-- At-a-glance --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-900 mb-1 m-0">Data is Secure</p>
                <p class="text-xs text-gray-500 m-0">All data encrypted in transit and at rest</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-900 mb-1 m-0">Never Sold</p>
                <p class="text-xs text-gray-500 m-0">Your data is never sold or shared with advertisers</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5 text-center">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-900 mb-1 m-0">You're in Control</p>
                <p class="text-xs text-gray-500 m-0">Disconnect your email or request data deletion anytime</p>
            </div>
        </div>

        {{-- Section 1 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-red-50">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>1. Who We Are (Data Controller)</h2>
                    <p class="m-0">
                        The entity responsible for your personal data collected through JARVIES is:<br><br>
                        <strong>PT Eclectic Consulting</strong><br>
                        Email: <a href="mailto:support@eclecticoffice.com">support@eclecticoffice.com</a><br>
                        Website: <a href="https://www.eclecticoffice.com" target="_blank">www.eclecticoffice.com</a>
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 2 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-amber-50">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>2. What Data We Collect</h2>
                    <p>We only collect what is necessary to provide the support service:</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Examples</th>
                                <th>Why We Need It</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Account data</strong></td>
                                <td>Name, email, phone, company name</td>
                                <td>To identify you and manage your account</td>
                            </tr>
                            <tr>
                                <td><strong>Ticket data</strong></td>
                                <td>Subject, description, messages, attachments</td>
                                <td>To process and resolve your support requests</td>
                            </tr>
                            <tr>
                                <td><strong>OAuth token</strong></td>
                                <td>Access token, refresh token, linked email</td>
                                <td>To send ticket emails on your behalf (optional feature)</td>
                            </tr>
                            <tr>
                                <td><strong>Usage data</strong></td>
                                <td>Login time, actions in the portal</td>
                                <td>Security audit and service improvement</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section 3 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-green-50">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>3. How We Use Your Data</h2>
                    <ul>
                        <li>To authenticate you and keep your account secure</li>
                        <li>To receive, process, and respond to your support tickets</li>
                        <li>To send ticket notification emails on your behalf (only when you use the email link feature)</li>
                        <li>To communicate updates about your open tickets</li>
                        <li>To improve the quality of our support service</li>
                        <li>To comply with legal obligations</li>
                    </ul>
                    <div class="bg-green-50 border border-green-100 rounded-xl px-4 py-3 mt-1">
                        <p class="text-xs text-green-800 m-0 font-medium">
                            ✅ We use your data <strong>only</strong> for delivering the support service described above — nothing else.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-purple-50">
                    <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>4. Email Integration (OAuth)</h2>
                    <p>
                        If you choose to connect your <strong>Microsoft (Outlook)</strong> or <strong>Google (Gmail)</strong>
                        account, we store your OAuth access and refresh tokens to send ticket emails from your address.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                            <p class="text-xs font-bold text-green-800 mb-2 m-0">✅ We DO</p>
                            <ul class="m-0">
                                <li class="text-xs">Send ticket emails on your behalf when you submit</li>
                                <li class="text-xs">Read your name and email address for identification</li>
                                <li class="text-xs">Store tokens securely (encrypted)</li>
                            </ul>
                        </div>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                            <p class="text-xs font-bold text-red-700 mb-2 m-0">❌ We DON'T</p>
                            <ul class="m-0">
                                <li class="text-xs">Read, scan, or store your inbox</li>
                                <li class="text-xs">Send emails without your action</li>
                                <li class="text-xs">Share your tokens with any third party</li>
                                <li class="text-xs">Access emails unrelated to support</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mt-3 m-0 text-sm">
                        You can <strong>disconnect</strong> your email account at any time from within JARVIES.
                        Upon disconnection, your tokens are <strong>permanently deleted</strong> from our systems immediately.
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 5 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-red-50">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>5. Who We Share Data With</h2>
                    <p>We <strong>do not sell, rent, or share</strong> your personal data with third parties, except:</p>
                    <div class="space-y-2 mt-2">
                        <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3">
                            <span class="text-lg mt-0.5">🏢</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 m-0">Internal support staff</p>
                                <p class="text-xs text-gray-500 m-0">Helpdesk agents at PT Eclectic Consulting who handle your tickets</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3">
                            <span class="text-lg mt-0.5">🔑</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 m-0">Microsoft / Google (OAuth only)</p>
                                <p class="text-xs text-gray-500 m-0">Only when you choose to connect your email. Subject to their respective privacy policies.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3">
                            <span class="text-lg mt-0.5">⚖️</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 m-0">Legal requirements</p>
                                <p class="text-xs text-gray-500 m-0">If required by law, court order, or regulatory authority</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 6 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-indigo-50">
                    <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>6. How Long We Keep Your Data</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                            <p class="text-2xl font-bold text-indigo-800 m-0">Instant</p>
                            <p class="text-xs text-indigo-600 mt-1 m-0">OAuth tokens deleted upon disconnection</p>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                            <p class="text-2xl font-bold text-indigo-800 m-0">Active</p>
                            <p class="text-xs text-indigo-600 mt-1 m-0">Account data kept while your contract is active</p>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
                            <p class="text-2xl font-bold text-indigo-800 m-0">2 Years</p>
                            <p class="text-xs text-indigo-600 mt-1 m-0">Minimum ticket history for audit purposes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 7 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-green-50">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>7. How We Protect Your Data</h2>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2.5">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">HTTPS encryption in transit</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2.5">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">OAuth tokens encrypted at rest</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2.5">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">Role-based access control</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2.5">
                            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">Regular security reviews</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 8 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-amber-50">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>8. Your Rights</h2>
                    <p>Under applicable privacy laws (including Indonesia's UU PDP), you have the right to:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <span class="text-base mt-0.5">👁️</span>
                            <div><p class="text-sm font-semibold text-gray-800 m-0">Access</p><p class="text-xs text-gray-500 m-0">Request a copy of your personal data</p></div>
                        </div>
                        <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <span class="text-base mt-0.5">✏️</span>
                            <div><p class="text-sm font-semibold text-gray-800 m-0">Correction</p><p class="text-xs text-gray-500 m-0">Request correction of inaccurate data</p></div>
                        </div>
                        <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <span class="text-base mt-0.5">🗑️</span>
                            <div><p class="text-sm font-semibold text-gray-800 m-0">Deletion</p><p class="text-xs text-gray-500 m-0">Request permanent deletion of your data</p></div>
                        </div>
                        <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <span class="text-base mt-0.5">🔌</span>
                            <div><p class="text-sm font-semibold text-gray-800 m-0">Disconnect</p><p class="text-xs text-gray-500 m-0">Revoke email OAuth access at any time</p></div>
                        </div>
                    </div>
                    <p class="mt-3 m-0 text-sm">
                        To exercise any right, email us at
                        <a href="mailto:support@eclecticoffice.com">support@eclecticoffice.com</a>.
                        We will respond within <strong>14 business days</strong>.
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 9 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-gray-100">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>9. Third-Party Services</h2>
                    <p>JARVIES integrates with these services only when you opt in:</p>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24">
                                <path d="M11.4 2H2v9.4h9.4V2z" fill="#F25022"/>
                                <path d="M22 2h-9.4v9.4H22V2z" fill="#7FBA00"/>
                                <path d="M11.4 12.6H2V22h9.4v-9.4z" fill="#00A4EF"/>
                                <path d="M22 12.6h-9.4V22H22v-9.4z" fill="#FFB900"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800 m-0">Microsoft Identity Platform</p>
                                <p class="text-xs text-gray-500 m-0">Used for Microsoft account (Outlook) OAuth login</p>
                            </div>
                            <a href="https://privacy.microsoft.com" target="_blank" class="text-xs shrink-0">Privacy Policy →</a>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800 m-0">Google OAuth 2.0</p>
                                <p class="text-xs text-gray-500 m-0">Used for Google account (Gmail) OAuth login</p>
                            </div>
                            <a href="https://policies.google.com/privacy" target="_blank" class="text-xs shrink-0">Privacy Policy →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 10 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-indigo-50">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>10. Changes to This Statement</h2>
                    <p class="m-0">
                        We may update this Privacy Statement to reflect changes in our practices or applicable laws.
                        When we do, we will update the "Last updated" date above and notify registered users via email
                        for any significant changes.
                    </p>
                </div>
            </div>
        </div>

        {{-- Contact CTA --}}
        <div class="bg-linear-to-r from-red-900 to-red-700 rounded-2xl p-6 text-white mt-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-white text-base mb-1">Questions about your privacy?</p>
                    <p class="text-white/70 text-sm m-0">
                        Contact our team at
                        <a href="mailto:support@eclecticoffice.com" class="text-white font-semibold underline">support@eclecticoffice.com</a>
                        — we'll respond within 14 business days.
                    </p>
                </div>
            </div>
        </div>

    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-100 mt-8 py-6">
        <div class="max-w-4xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-gray-400">
            <span>&copy; {{ date('Y') }} PT Eclectic Consulting. All rights reserved.</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('legal.terms') }}" class="hover:text-gray-600">Terms of Service</a>
                <span>·</span>
                <a href="{{ route('legal.privacy') }}" class="hover:text-gray-600 font-medium text-red-700">Privacy Statement</a>
            </div>
        </div>
    </footer>

</body>
</html>
