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
    'frontend_public_url' => rtrim(env('FRONTEND_PUBLIC_URL', env('FRONTEND_HOME_URL', '/')), '/'),
    'public_hotel_url_mode' => env('PUBLIC_HOTEL_URL_MODE', 'path'),
    'public_hotel_path_prefix' => trim(env('PUBLIC_HOTEL_PATH_PREFIX', 'properties'), '/'),
];
