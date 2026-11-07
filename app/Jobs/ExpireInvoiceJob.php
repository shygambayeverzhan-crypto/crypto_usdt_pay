<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $invoiceId,
    ) {
        $this->onQueue('invoices');
    }

    public function handle(InvoiceServiceContract $service): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);
        if (!$invoice) {
            return;
        }

        // Экспирируем только если срок вышел и инвойс ещё активен
        if ($invoice->expires_at && now()->greaterThanOrEqualTo($invoice->expires_at)) {
            $service->expire($invoice);
        }
    }
}


