<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Money\MoneyServiceContract;
use App\Contracts\WalletTransfer\WalletTransferServiceContract;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSandboxInvoicePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $invoiceId,
    ) {
        $this->onQueue('sandbox-payments');
    }

    public function handle(WalletTransferServiceContract $transfer, MoneyServiceContract $money): void
    {
        $invoice = Invoice::query()
            ->with(['address'])
            ->find($this->invoiceId);

        if (!$invoice) {
            return;
        }

        if ($invoice->currency !== Currency::USDT || $invoice->network !== Network::TRON) {
            return;
        }

        if ($invoice->status !== InvoiceStatus::PENDING) {
            return;
        }

        if ($invoice->expires_at && now()->greaterThanOrEqualTo($invoice->expires_at)) {
            return;
        }

        if (!empty($invoice->txid)) {
            return;
        }

        $to = (string) ($invoice->address?->address ?? '');
        if ($to === '') {
            return;
        }

        $amount = $money->format($invoice->amount);
        $idempotencyKey = 'invoice_' . $invoice->id;

        $ok = $transfer->sendUsdt($to, $amount, $idempotencyKey);

        if (!$ok) {
            Log::error('SandboxInvoicePayment: transfer failed', [
                'invoice_id' => $invoice->id,
                'to' => $to,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        Log::info('SandboxInvoicePayment: transfer sent', [
            'invoice_id' => $invoice->id,
            'to' => $to,
            'amount' => $amount,
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}


