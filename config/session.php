<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | File driver provides the best balance of performance and simplicity
    | for Jarvies portal. Session files stored in storage/framework/sessions
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | 10080 minutes = 7 days
    | Matches the Ecosystem API token expiry time for consistency
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 10080),

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | FALSE for performance optimization
    | The API token from Ecosystem is already encrypted
    | Session data (user info, token) doesn't need double encryption
    |
    | IMPORTANT: Set to TRUE in production if storing sensitive data
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the "file" session driver, sessions are stored here
    | Ensure this directory is writable by the web server
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | Not used when driver is "file"
    | Only relevant for database session driver
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | [2, 100] = 2% chance per request to run garbage collection
    | Cleans up expired session files automatically
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | SECURITY SETTINGS:
    | - http_only: TRUE (prevent JavaScript access - XSS protection)
    | - same_site: 'lax' (CSRF protection while allowing normal navigation)
    | - secure: FALSE for localhost, TRUE for production HTTPS
    | - domain: NULL for localhost compatibility
    |
    */

    'cookie' => env('SESSION_COOKIE', 'jarvies_session'),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE', false),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Chrome's CHIPS (Cookies Having Independent Partitioned State)
    | Not needed for Jarvies portal
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];