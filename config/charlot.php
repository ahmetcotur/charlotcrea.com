<?php

return [
    'delivery_file_max_kb' => (int) env('DELIVERY_FILE_MAX_KB', 262144),
    'indexable' => env('SITE_INDEXABLE', false),
    'page_cache_seconds' => (int) env('PAGE_CACHE_SECONDS', 300),
    'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'info@charlotcrea.com')),
];
