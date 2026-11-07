<?php

$trongridNetwork = env('TRONGRID_NETWORK', 'nile');
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
        'network' => $trongridNetwork,
        // Автовыбор сети: через TRONGRID_NETWORK=mainnet|nile, иначе по APP_ENV
        // Можно переопределить конкретными переменными TRONGRID_BASE_URL/USDT_CONTRACT
        'base_url' => env(
            'TRONGRID_BASE_URL',
            $isMainnet ? '' : env('TRONGRID_TESTNET_BASE_URL', '')
        ),

        'api_key' => env('TRONGRID_API_KEY'),

        // Token contracts must be supplied for the selected network.
        'usdt_contract' => env(
            'TRONGRID_USDT_CONTRACT',
            $isMainnet ? '' : env('TRONGRID_TESTNET_USDT_CONTRACT', '')
        ),
    ],
    'tronscan' => [
        'base_url' => env(
            'TRONSCAN_BASE_URL',
            $isMainnet ? '' : env('TRONSCAN_TESTNET_BASE_URL', '')
        ),
    ],

    // Публичный API для мерчантов
    'public_api' => [
        'key' => env('PUBLIC_API_KEY', ''),
    ],

    'ipgeolocation' => [
        'base_url' => env('IPGEOLOCATION_BASE_URL', ''),
        'api_key' => env('IPGEOLOCATION_API_KEY'),
    ],

];
