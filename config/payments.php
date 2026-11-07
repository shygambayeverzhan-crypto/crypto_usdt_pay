<?php

return [
    'receive_address' => env('PAYMENT_RECEIVE_ADDRESS', ''),
    'min_confirmations' => max(20, (int) env('PAYMENT_MIN_CONFIRMATIONS', 20)),
    'reconcile_hours' => 168,
    // Callbacks stay disabled until an operator explicitly enables reviewed destinations.
    'callbacks_enabled' => (bool) env('PAYMENT_CALLBACKS_ENABLED', false),
];
