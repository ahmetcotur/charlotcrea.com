<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),
    'country_database' => storage_path('app/private/analytics/country.mmdb'),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
];
