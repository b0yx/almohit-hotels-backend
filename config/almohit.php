<?php

return [
    'public_base_domain' => strtolower(trim(env('PUBLIC_BASE_DOMAIN', 'almohit.com'), '.')),
    'reserved_subdomains' => array_values(array_filter(array_map(
        fn (string $value): string => strtolower(trim($value)),
        explode(',', env('PUBLIC_RESERVED_SUBDOMAINS', 'admin,api,www,mail,media,static,support'))
    ))),
    'frontend_home_url' => env('FRONTEND_HOME_URL', 'http://localhost:3000/'),
    'frontend_admin_url' => env('FRONTEND_ADMIN_URL', 'http://localhost:3000/admin/'),
    'frontend_customer_url' => env('FRONTEND_CUSTOMER_URL', 'http://localhost:3000/profile/'),
];
