<?php

$trongridNetwork = env('TRONGRID_NETWORK', env('APP_ENV', 'local') === 'production' ? 'mainnet' : 'nile');
$isMainnet = strtolower((string) $trongridNetwork) === 'mainnet';

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'trongrid' => [
        // Автовыбор сети: через TRONGRID_NETWORK=mainnet|nile, иначе по APP_ENV
        // Можно переопределить конкретными переменными TRONGRID_BASE_URL/USDT_CONTRACT
        'base_url' => env(
            'TRONGRID_BASE_URL',
            $isMainnet ? 'https://api.trongrid.io' : env('TRONGRID_TESTNET_BASE_URL', 'https://api.nile.trongrid.io')
        ),

        'api_key' => env('TRONGRID_API_KEY'),

        // Контракт USDT: для mainnet дефолт стандартный; для nile — из TRONGRID_TESTNET_USDT_CONTRACT
        'usdt_contract' => env(
            'TRONGRID_USDT_CONTRACT',
            $isMainnet ? 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t' : env('TRONGRID_TESTNET_USDT_CONTRACT', '')
        ),
    ],
    'tronscan' => [
        'base_url' => env(
            'BASE_URL',
            $isMainnet ? 'https://tronscan.org' : env('TRONSCAN_TESTNET_BASE_URL', 'https://nile.tronscan.org')
        ),
    ],

    // Публичный API для мерчантов
    'public_api' => [
        'key' => env('PUBLIC_API_KEY', ''),
    ],

    'ipgeolocation' => [
        'base_url' => env('IPGEOLOCATION_BASE_URL', 'https://api.ipgeolocation.io/v2'),
        'api_key' => env('IPGEOLOCATION_API_KEY'),
    ],

];
