<script setup lang="ts">
import { vueLang } from '@erag/lang-sync-inertia';

const { __ } = vueLang();

const callbackExample = `{
  "event": "status_changed",
  "invoice": {
    "id": "01JABCDE1234XYZ",
    "external_invoice_id": "order-100500",
    "payment_url": "https://pay.example.com/invoices/01JABCDE1234XYZ",
    "amount": "12.34000000",
    "currency": "usdt",
    "currency_label": "USDT",
    "network": "tron",
    "network_label": "TRON",
    "status": "paid",
    "txid": "0xabc...",
    "tx_explorer_url": "https://tronscan.org/#/transaction/0xabc...",
    "amount_received": "12.34000000",
    "confirmations": 12,
    "expires_at": "2025-01-01T10:00:00.000000Z",
    "callback_url": "https://merchant.test/callback",
    "tag": "order-100500",
    "metadata": {
      "order_id": "100500"
    },
    "merchant_id": "01MERCHANTID",
    "client_id": "01CLIENTID",
    "client_external_id": "customer-123",
    "product_name": "Order #100500",
    "product_description": "Test payment",
    "created_at": "2025-01-01T09:00:00.000000Z",
    "updated_at": "2025-01-01T09:05:00.000000Z"
  }
}`;
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h3 class="card-title">{{ __('frontend.api.documentation.callbacks.title') }}</h3>
            <div class="space-y-1">
                <p class="text-sm text-base-content/80">
                    {{ __('frontend.api.documentation.callbacks.description') }}
                </p>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="bg-base-200 rounded-box p-4 space-y-2">
                    <h5 class="font-semibold text-sm">
                        {{ __('frontend.api.documentation.callbacks.events_title') }}
                    </h5>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        <li>{{ __('frontend.api.documentation.callbacks.events.created') }}</li>
                        <li>{{ __('frontend.api.documentation.callbacks.events.status_changed') }}</li>
                    </ul>
                </div>
                <div class="bg-base-200 rounded-box p-4 space-y-2">
                    <h5 class="font-semibold text-sm">
                        {{ __('frontend.api.documentation.callbacks.headers_title') }}
                    </h5>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        <li>{{ __('frontend.api.documentation.callbacks.headers.event', { event: 'status_changed' }) }}</li>
                        <li>{{ __('frontend.api.documentation.callbacks.headers.token') }}</li>
                        <li>{{ __('frontend.api.documentation.callbacks.headers.content_type') }}</li>
                    </ul>
                </div>
            </div>
            <div class="bg-base-200 rounded-box p-4 space-y-2">
                <h5 class="font-semibold text-sm">
                    {{ __('frontend.api.documentation.callbacks.delivery_title') }}
                </h5>
                <ul class="list-disc list-inside text-sm space-y-1">
                    <li>{{ __('frontend.api.documentation.callbacks.delivery.method') }}</li>
                    <li>{{ __('frontend.api.documentation.callbacks.delivery.success') }}</li>
                    <li>{{ __('frontend.api.documentation.callbacks.delivery.retries') }}</li>
                </ul>
            </div>
            <div class="bg-base-200 rounded-box p-4 space-y-2">
                <h5 class="font-semibold text-sm">
                    {{ __('frontend.api.documentation.callbacks.payload_title') }}
                </h5>
                <p class="text-sm text-base-content/80">
                    {{ __('frontend.api.documentation.callbacks.payload_hint') }}
                </p>
                <pre class="mockup-code whitespace-pre-wrap text-xs"><code>{{ callbackExample }}</code></pre>
            </div>
        </div>
    </div>
</template>

