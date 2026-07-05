<?php

return [
    'public_base_domain' => strtolower(trim(env('PUBLIC_BASE_DOMAIN', 'almohit.com'), '.')),
    'reserved_subdomains' => array_values(array_filter(array_map(
        fn (string $value): string => strtolower(trim($value)),
        explode(',', env('PUBLIC_RESERVED_SUBDOMAINS', 'admin,api,www,mail,media,static,support'))
    ))),
    'frontend_home_url' => env('FRONTEND_HOME_URL', '/'),
    'frontend_admin_url' => env('FRONTEND_ADMIN_URL', '/admin/'),
    'frontend_customer_url' => env('FRONTEND_CUSTOMER_URL', '/profile/'),
    'public_cache_ttl' => (int) env('PUBLIC_CACHE_TTL', 600),
    'slow_api_log_threshold_ms' => (int) env('SLOW_API_LOG_THRESHOLD_MS', 500),
    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 300),
        'public_read_per_minute' => (int) env('PUBLIC_READ_RATE_LIMIT_PER_MINUTE', 180),
        'booking_write_per_minute' => (int) env('BOOKING_WRITE_RATE_LIMIT_PER_MINUTE', 10),
    ],
];
