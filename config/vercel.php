<?php

return [
    // Shared safeguards for managed containers; keep the old config name compatible.
    'enabled' => (bool) env('RAILWAY_RUNTIME', false) || (bool) env('VERCEL_RUNTIME', false),
    'cron_secret' => env('CRON_SECRET', ''),
    // Enable only after migrations, network configuration and scheduler acceptance.
    'payments_enabled' => (bool) env('PAYMENTS_ENABLED', env('VERCEL_PAYMENTS_ENABLED', false)),
];
