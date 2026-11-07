<?php

return [
    'enabled' => (bool) env('RAILWAY_RUNTIME', false),
    'auto_webhook' => (bool) env('TELEGRAM_AUTO_WEBHOOK', false),
];
