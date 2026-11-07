<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConfirmInvoicePaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $invoiceId,
        public readonly int $minConfirmations = 10,
    ) {
        $this->onQueue('invoices');
    }

    public function handle(InvoiceServiceContract $service): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);
        if (!$invoice) {
            return;
        }

        // Если инвойс уже финализирован — прекращаем
        if ($invoice->status->isFinal()) {
            return;
        }

        // Если истёк — отдельно обработает ExpireInvoiceJob, здесь просто выходим
        if ($invoice->expires_at && now()->greaterThanOrEqualTo($invoice->expires_at)) {
            return;
        }

        // Продолжаем только в статусе PROCESSING
        if ($invoice->status !== InvoiceStatus::PROCESSING) {
            return;
        }

        $finalized = $service->finalizeIfConfirmed($invoice, $this->minConfirmations);
        if ($finalized) {
            return; // помечен как PAID
        }

        // Иначе продолжаем проверять раз в минуту
        self::dispatch($invoice->id, $this->minConfirmations)->delay(now()->addMinute());
    }
}


