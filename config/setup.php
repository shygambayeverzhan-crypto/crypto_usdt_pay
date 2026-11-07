<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME', ''),
        'email' => env('ADMIN_EMAIL', ''),
        'password' => env('ADMIN_PASSWORD', ''),
    ],
    'sandbox_wallet' => [
        'id' => env('SANDBOX_WALLET_ID', ''),
        'network' => env('SANDBOX_WALLET_NETWORK', 'nile'),
    ],
];
