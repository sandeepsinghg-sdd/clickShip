<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'create-draft-order'], // Add your Laravel route here
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // OR use the Shopify store URL instead of '*'
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
