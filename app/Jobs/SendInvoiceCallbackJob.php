<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\Invoice\InvoiceCallbackException;
use App\Http\Resources\InvoiceResource;
use App\Models\ApiToken;
use App\Models\Invoice;
use App\Models\InvoiceCallbackLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendInvoiceCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $invoiceId,
        public string $event,
    ) {
        $this->onQueue('callbacks');
    }

    public function handle(): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);
        if (! $invoice || empty($invoice->callback_url)) {
            return;
        }

        $invoice->loadMissing('user');

        $callbackUrl = $invoice->callback_url;
        $callbackToken = $invoice->user?->apiTokens()
            ->firstOrCreate(
                ['name' => ApiToken::NAME_CALLBACK],
                ['token' => ApiToken::generateUniqueToken()]
            )
            ->token;

        $payload = [
            'event' => $this->event,
            'invoice' => (new InvoiceResource($invoice))->resolve(),
        ];

        $log = new InvoiceCallbackLog([
            'invoice_id' => $invoice->id,
            'event' => $this->event,
            'url' => $callbackUrl,
            'request_payload' => $payload,
        ]);

        $startedAt = microtime(true);

        try {
            $response = Http::asJson()
                ->withoutRedirecting()
                ->timeout(10)
                ->withHeaders([
                    'X-Callback-Event' => $this->event,
                    'X-Callback-Token' => $callbackToken,
                ])
                ->post($callbackUrl, $payload);

            if (! $response->successful()) {
                throw new InvoiceCallbackException(__('messages.api.callback_failed', [
                    'status' => $response->status(),
                ]));
            }
            $log->response_status = $response->status();
            $log->response_body = $response->body();
        } catch (\Throwable $throwable) {
            $log->response_status = isset($response) ? $response->status() : null;
            $log->response_body = isset($response) ? $response->body() : null;
            $log->error_message = $throwable->getMessage();
        } finally {
            $log->duration_ms = (int) round((microtime(true) - $startedAt) * 1000);
            $log->save();
        }
    }
}
