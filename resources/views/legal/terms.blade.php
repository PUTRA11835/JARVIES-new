<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — JARVIES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.5rem 2rem; margin-bottom: 1.25rem; }
        .section-card:hover { border-color: #fca5a5; box-shadow: 0 4px 16px rgba(153,27,27,0.06); transition: all 0.2s; }
        .icon-box { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        h2 { font-size: 1rem; font-weight: 700; color: #111827; margin-bottom: 0.75rem; }
        p  { color: #4b5563; line-height: 1.75; font-size: 0.9375rem; margin-bottom: 0.75rem; }
        ul { padding-left: 0; list-style: none; margin-bottom: 0.75rem; }
        ul li { color: #4b5563; line-height: 1.75; font-size: 0.9375rem; padding-left: 1.25rem; position: relative; margin-bottom: 0.25rem; }
        ul li::before { content: "•"; color: #991b1b; font-weight: bold; position: absolute; left: 0; }
        a { color: #991b1b; text-decoration: underline; }
        a:hover { color: #7f1d1d; }
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
            <a href="{{ route('legal.privacy') }}"
               class="text-white/70 hover:text-white text-sm font-medium transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Privacy Statement
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <div class="bg-linear-to-b from-red-900/5 to-transparent border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-6 py-10">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center shrink-0 mt-1">
                    <svg class="w-7 h-7 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Terms of Service</h1>
                    <p class="text-gray-500 text-sm mt-1">Last updated: April 2, 2026 &nbsp;·&nbsp; Effective immediately upon use</p>
                    <div class="mt-3 inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium px-3 py-1.5 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        By using JARVIES, you agree to these terms
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <main class="max-w-4xl mx-auto px-6 py-10 space-y-3">

        {{-- Quick summary --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 mb-6">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-bold text-blue-800">Quick Summary</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-xs text-blue-900 m-0">Use JARVIES to submit & track your support tickets</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-xs text-blue-900 m-0">Email linking is optional — only used to send ticket notifications</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-xs text-blue-900 m-0">Your data is kept confidential and never sold to third parties</p>
                </div>
            </div>
        </div>

        {{-- Section 1 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-red-50">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>1. Acceptance of Terms</h2>
                    <p>
                        Welcome to <strong>JARVIES</strong> — the customer support portal operated by
                        <strong>PT Eclectic Consulting</strong>. By accessing or using this application,
                        you confirm that you are authorized to act on behalf of your organization and
                        agree to comply with these Terms of Service.
                    </p>
                    <p class="m-0">If you do not agree to these terms, please do not use this service.</p>
                </div>
            </div>
        </div>

        {{-- Section 2 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-blue-50">
                    <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>2. What is JARVIES?</h2>
                    <p>JARVIES is a customer support portal that allows registered customers of PT Eclectic Consulting to:</p>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span class="text-sm text-gray-700">Submit support tickets</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm text-gray-700">Track ticket status</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span class="text-sm text-gray-700">Chat with support team</span>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-gray-700">Receive email notifications</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-amber-50">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>3. Your Account Responsibilities</h2>
                    <p>Your account is personal and tied to your organization. You are responsible for:</p>
                    <ul>
                        <li>Keeping your login credentials confidential</li>
                        <li>All activity that occurs under your account</li>
                        <li>Notifying us immediately if you suspect unauthorized access</li>
                    </ul>
                    <div class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 mt-2">
                        <p class="text-xs text-amber-800 font-medium m-0">
                            ⚠️ Do not share your account. Each account must belong to one authorized person from your organization.
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
                    <h2>4. Email Integration (Optional)</h2>
                    <p>
                        JARVIES offers an optional feature to connect your <strong>Microsoft (Outlook)</strong> or
                        <strong>Google (Gmail)</strong> account. This allows ticket replies to be sent directly to your inbox.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                            <p class="text-xs font-bold text-green-700 mb-2 m-0">✅ What we DO</p>
                            <ul class="m-0">
                                <li class="text-xs">Send ticket-related emails on your behalf</li>
                                <li class="text-xs">Read your basic profile (name & email)</li>
                            </ul>
                        </div>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                            <p class="text-xs font-bold text-red-700 mb-2 m-0">❌ What we DON'T do</p>
                            <ul class="m-0">
                                <li class="text-xs">Read your personal inbox</li>
                                <li class="text-xs">Send emails without your action</li>
                                <li class="text-xs">Share your tokens with anyone</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mt-3 m-0 text-sm">You can disconnect your email account at any time from the portal settings.</p>
                </div>
            </div>
        </div>

        {{-- Section 5 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-red-50">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>5. Prohibited Use</h2>
                    <p>To keep JARVIES safe and reliable for all customers, you agree <strong>not</strong> to:</p>
                    <ul>
                        <li>Submit false, misleading, or fraudulent support requests</li>
                        <li>Upload harmful, malicious, or illegal content</li>
                        <li>Attempt to access other users' accounts or systems</li>
                        <li>Use the portal in any way that violates applicable laws</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Section 6 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-green-50">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>6. Privacy</h2>
                    <p class="m-0">
                        Your use of JARVIES is governed by our
                        <a href="{{ route('legal.privacy') }}">Privacy Statement</a>,
                        which explains in detail how we collect, use, and protect your personal data.
                        We recommend reading it alongside these Terms.
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 7 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-gray-100">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>7. Service Availability</h2>
                    <p class="m-0">
                        We strive to keep JARVIES available at all times. However, scheduled maintenance,
                        updates, or unforeseen technical issues may cause brief downtime. We will notify
                        you in advance for planned maintenance whenever possible.
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 8 --}}
        <div class="section-card">
            <div class="flex items-start gap-4">
                <div class="icon-box bg-indigo-50">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2>8. Changes to These Terms</h2>
                    <p class="m-0">
                        We may update these Terms from time to time. When we do, we will update the
                        "Last updated" date above and notify registered users via email for significant changes.
                        Continued use of JARVIES after changes are published means you accept the updated terms.
                    </p>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-linear-to-r from-red-900 to-red-800 rounded-2xl p-6 text-white mt-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-white text-base mb-1">Have questions about these terms?</p>
                    <p class="text-white/70 text-sm m-0">
                        Contact us at
                        <a href="mailto:support@eclecticoffice.com" class="text-white font-semibold underline">support@eclecticoffice.com</a>
                        and we'll be happy to help.
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
                <a href="{{ route('legal.terms') }}" class="hover:text-gray-600 font-medium text-red-700">Terms of Service</a>
                <span>·</span>
                <a href="{{ route('legal.privacy') }}" class="hover:text-gray-600">Privacy Statement</a>
            </div>
        </div>
    </footer>

</body>
</html>
