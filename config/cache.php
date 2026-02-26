<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | FILE cache chosen for Jarvies because:
    | 1. No database required (Jarvies is frontend-only)
    | 2. Simple and reliable for development
    | 3. Good performance for small-to-medium data
    | 4. No external dependencies (Redis, Memcached)
    |
    | For production with high traffic, consider Redis
    |
    */

    'default' => env('CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Available cache drivers configured below
    | Jarvies primarily uses 'file' and 'array' (testing)
    |
    */

    'stores' => [

        /*
        |--------------------------------------------------------------------------
        | Array Cache (Runtime Only)
        |--------------------------------------------------------------------------
        |
        | In-memory cache, cleared on every request
        | Used for testing and temporary data within single request
        |
        */

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Database Cache (NOT USED)
        |--------------------------------------------------------------------------
        |
        | Requires 'cache' table in database
        | Jarvies doesn't use database, so this is disabled
        |
        */

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        /*
        |--------------------------------------------------------------------------
        | File Cache (DEFAULT)
        |--------------------------------------------------------------------------
        |
        | Performance optimized file cache
        | - Fast read/write for small datasets
        | - Automatic garbage collection
        | - No external dependencies
        |
        | Cache stored in: storage/framework/cache/data
        |
        */

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Memcached Cache (Optional)
        |--------------------------------------------------------------------------
        |
        | For production scaling, install Memcached
        | Faster than file cache for high concurrency
        |
        */

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Redis Cache (Recommended for Production)
        |--------------------------------------------------------------------------
        |
        | Best performance for high-traffic production
        | Requires Redis server installed
        |
        */

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        /*
        |--------------------------------------------------------------------------
        | DynamoDB Cache (AWS)
        |--------------------------------------------------------------------------
        */

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Octane Cache
        |--------------------------------------------------------------------------
        */

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prevents cache key collisions if multiple apps share same cache server
    | Format: jarvies_cache_
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'jarvies'), '_').'_cache_'),

];