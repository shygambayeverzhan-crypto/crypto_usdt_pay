<?php

namespace App\Console\Commands;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Recover and verify outstanding payments from durable invoice state';

    public function handle(InvoiceServiceContract $service): int
    {
        $failed = false;
        Invoice::query()->whereNotNull('chain_network')->where(function ($q) {
            $q->where('status', 'processing')->orWhere(function ($q) {
                $q->whereIn('status', ['pending', 'expired'])->where('created_at', '>=', now()->subHours(config('payments.reconcile_hours')));
            });
        })->chunkById(100, function ($invoices) use ($service, &$failed) {
            foreach ($invoices as $invoice) {
                try {
                    if ($invoice->status !== InvoiceStatus::PROCESSING) {
                        $service->attachExactIncomingPayment($invoice);
                    }
                    $service->finalizeIfConfirmed($invoice);
                    $service->expire($invoice);
                } catch (\Throwable $e) {
                    $failed = true;
                    report($e);
                    $this->error('Reconciliation failed for invoice '.$invoice->id);
                }
            }
        });
        Invoice::query()->where('status', 'paid')->whereNotNull('customer_telegram_chat_id')
            ->whereNull('customer_notified_at')->chunkById(100, function ($invoices) use (&$failed) {
                foreach ($invoices as $invoice) {
                    try {
                        Telegram::sendMessage(['chat_id' => $invoice->customer_telegram_chat_id,
                            'text' => 'Оплата подтверждена в сети TRON. Заказ: '.$invoice->id]);
                        $invoice->forceFill(['customer_notified_at' => now()])->save();
                    } catch (\Throwable $e) {
                        $failed = true;
                        \Illuminate\Support\Facades\Log::warning('Telegram payment notification failed', ['exception_class' => $e::class]);
                    }
                }
            });
        if (! $failed) {
            Cache::put('payments:last_success', now()->toIso8601String(), now()->addDays(2));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
