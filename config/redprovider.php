<?php

return [
    'base_url' => env('REDPROVIDER_BASE_URL', 'https://localhost:3000'),

    'client_id' => env('REDPROVIDER_CLIENT_ID', 'Fun'),
    'client_secret' => env('REDPROVIDER_CLIENT_SECRET', '=work@red'),

    'ssl_cert_path' => env('REDPROVIDER_SSL_CERT_PATH', base_path('ssl_cert.pem')),

    'use_mock' => env('REDPROVIDER_USE_MOCK', true),
];
