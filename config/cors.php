<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'create-draft-order',
        'shopify/shipping/settings',
        'shopify/all-shipping-rate', // No leading slash
    ],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

