<?php

return [
    'enabled' => (bool) env('VERCEL_RUNTIME', false),
    'cron_secret' => env('CRON_SECRET', ''),
    // Enable only after migrations, network configuration and scheduler acceptance.
    'payments_enabled' => (bool) env('VERCEL_PAYMENTS_ENABLED', false),
];
