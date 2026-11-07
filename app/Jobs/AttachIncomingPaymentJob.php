<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AttachIncomingPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 8;

    public int $timeout = 30;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $invoiceId,
    ) {
        $this->onQueue('invoices');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300, 600, 900];
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

        try {
            $updated = $service->attachExactIncomingPayment($invoice);
        } catch (ConnectionException|ConnectException $exception) {
            $this->release($this->backoffForAttempt());
            return;
        } catch (Throwable $exception) {
            if ($this->isTransientTimeout($exception)) {
                $this->release($this->backoffForAttempt());
                return;
            }

            throw $exception;
        }

        // Если платёж найден — переключаемся на проверку подтверждений
        if ($updated && $updated->status === InvoiceStatus::PROCESSING) {
            ConfirmInvoicePaymentJob::dispatch($updated->id)->delay(now()->addMinute());
            return;
        }

        // Иначе — пробуем снова через минуту, пока инвойс активен
        self::dispatch($invoice->id)->delay(now()->addMinute());
    }

    private function backoffForAttempt(): int
    {
        $attempt = max(1, $this->attempts());
        $backoff = $this->backoff();
        $index = min(count($backoff) - 1, $attempt - 1);

        return $backoff[$index] ?? 60;
    }

    private function isTransientTimeout(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 28')
            || str_contains($message, 'resolving timed out')
            || str_contains($message, 'operation timed out')
            || str_contains($message, 'connection timed out');
    }
}


