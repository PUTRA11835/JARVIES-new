<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Configuration for external service providers
    | Most are not used by Jarvies (frontend-only app)
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ecosystem API Configuration
    |--------------------------------------------------------------------------
    |
    | CRITICAL: Connection to Ecosystem backend. Set ECOSYSTEM_API_URL in .env
    | (e.g. https://ecosystem.example.com/api). No safe localhost fallback —
    | empty default so production misconfig fails loudly instead of silently
    | hitting a non-existent local server.
    |
    | Settings:
    | - url: API base URL
    | - timeout: HTTP request timeout (seconds)
    | - retry.times: Number of retry attempts on failure
    | - retry.sleep: Milliseconds to wait between retries
    |
    | Performance Notes:
    | - 30s timeout suitable for slow API responses
    | - 3 retries with 1s sleep = max 3s additional wait time
    | - Prevents temporary network issues from failing requests
    |
    */

    'ecosystem' => [
        'url'     => env('ECOSYSTEM_API_URL', ''),
        'api_key' => env('ECOSYSTEM_API_KEY', ''),
        'timeout' => env('ECOSYSTEM_API_TIMEOUT', 30),
        'retry' => [
            'times' => env('ECOSYSTEM_API_RETRY_TIMES', 3),
            'sleep' => env('ECOSYSTEM_API_RETRY_SLEEP', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers for Customer Email Linking
    |--------------------------------------------------------------------------
    | Used by OAuthEmailController + CustomerEmailService
    | to let customers send emails from their own inbox.
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'azure' => [
        'client_id'     => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect'      => env('AZURE_REDIRECT_URI'),
        'tenant'        => env('AZURE_TENANT_ID', 'common'),
    ],

];