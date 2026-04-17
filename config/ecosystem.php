<?php

return [
    // Full API base URL, e.g. https://ecosystem.domain.com/api
    'url'      => env('ECOSYSTEM_URL', ''),

    // Public base URL (no /api) for asset/storage links, e.g. https://ecosystem.domain.com
    // If not set, derived automatically by stripping /api suffix from ECOSYSTEM_URL.
    'base_url' => env('ECOSYSTEM_BASE_URL', ''),

    'api_key'  => env('ECOSYSTEM_API_KEY', ''),
];
