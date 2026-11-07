<?php

namespace App\Services\Invoice;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Blockchain\BudgetExhausted;
use App\Services\Blockchain\RequestBudget;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;

final class PaymentBatch
{
    public function __construct(private InvoiceServiceContract $service, private RequestBudget $budget) {}

    public function run(int $limit = 25, int $seconds = 40): array
    {
        // Shared PostgreSQL/Redis lock; no process-local or filesystem lock in Vercel.
        $lock = Cache::lock('payments:bounded-reconcile', 240);
        if (! $lock->get()) {
            return ['status' => 'busy', 'checked' => 0, 'failed' => 0];
        }
        $checked = $failed = 0;
        $exhausted = false;
        $this->budget->start(min(40, max(1, $seconds)));
        try {
            $invoices = Invoice::query()->whereNotNull('chain_network')->where(function ($q) {
                $q->where('status', 'processing')->orWhere(function ($q) {
                    $q->whereIn('status', ['pending', 'expired'])
                        ->where('created_at', '>=', now()->subHours(config('payments.reconcile_hours')));
                });
            })->orderByRaw('CASE WHEN last_checked_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('last_checked_at')->orderBy('id')->limit(min(100, max(1, $limit)))->get();
            foreach ($invoices as $invoice) {
                if ($this->budget->remaining() < 1) {
                    $exhausted = true;
                    break;
                }
                // Persist scheduling progress before I/O so a timeout cannot starve newer orders.
                $invoice->forceFill(['last_checked_at' => now()])->save();
                try {
                    if ($invoice->status !== InvoiceStatus::PROCESSING) {
                        $this->service->attachExactIncomingPayment($invoice);
                    }
                    $this->service->finalizeIfConfirmed($invoice);
                    $this->service->expire($invoice);
                    $checked++;
                } catch (BudgetExhausted $e) {
                    $exhausted = true;
                    break;
                } catch (\Throwable $e) {
                    $failed++;
                    report($e);
                }
            }
            $notifications = Invoice::query()->where('status', 'paid')->whereNotNull('customer_telegram_chat_id')
                ->whereNull('customer_notified_at')
                ->orderByRaw('CASE WHEN notification_attempted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('notification_attempted_at')->orderBy('id')->limit(10)->get();
            foreach ($notifications as $invoice) {
                if ($this->budget->remaining() < 2) {
                    $exhausted = true;
                    break;
                }
                $invoice->forceFill(['notification_attempted_at' => now()])->save();
                try {
                    Telegram::setTimeOut((int) min(10, $this->budget->remaining()));
                    Telegram::sendMessage(['chat_id' => $invoice->customer_telegram_chat_id,
                        'text' => 'Оплата подтверждена в сети TRON. Заказ: '.$invoice->id]);
                    $invoice->forceFill(['customer_notified_at' => now()])->save();
                } catch (\Throwable $e) {
                    $failed++;
                    \Illuminate\Support\Facades\Log::warning('Telegram payment notification failed', ['exception_class' => $e::class]);
                }
            }
            $result = ['status' => $failed ? 'error' : ($exhausted ? 'partial' : 'ok'),
                'checked' => $checked, 'failed' => $failed];
            Cache::put('payments:last_tick', $result + ['at' => now()->toIso8601String()], 86400);
            if (! $failed && ! $exhausted) {
                Cache::put('payments:last_success', now()->toIso8601String(), 86400);
            }

            return $result;
        } finally {
            $this->budget->clear();
            $lock->release();
        }
    }
}
