<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Contracts\Invoice\InvoiceCallbackServiceContract;
use App\Jobs\SendInvoiceCallbackJob;
use App\Models\Invoice;

class InvoiceCallbackService implements InvoiceCallbackServiceContract
{
    /**
     * {@inheritDoc}
     */
    public function send(Invoice $invoice, string $event): void
    {
        if (! config('payments.callbacks_enabled') || empty($invoice->callback_url)) {
            return;
        }
        SendInvoiceCallbackJob::dispatch($invoice->id, $event)->onQueue('callbacks');
    }
}
