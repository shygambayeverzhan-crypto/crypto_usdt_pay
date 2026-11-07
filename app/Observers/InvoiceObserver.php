<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Contracts\Invoice\InvoiceCallbackServiceContract;
use App\Contracts\Notification\NotificationServiceContract;
use App\Models\Invoice;
use App\Services\Notification\Events\InvoiceCreatedNotificationEvent;
use App\Services\Notification\Events\InvoiceStatusChangedNotificationEvent;

class InvoiceObserver
{
    /**
     * Срабатывает при создании инвойса.
     */
    public function created(Invoice $invoice): void
    {
        app(InvoiceCallbackServiceContract::class)->send($invoice, 'created');
        app(NotificationServiceContract::class)->dispatch(new InvoiceCreatedNotificationEvent($invoice));
    }

    /**
     * Срабатывает при обновлении инвойса. Отправляем callback только при изменении статуса.
     */
    public function updated(Invoice $invoice): void
    {
        if (!$invoice->wasChanged('status')) {
            return;
        }

        $previousStatusRaw = $invoice->getOriginal('status');
        $previousStatus = $previousStatusRaw instanceof InvoiceStatus
            ? $previousStatusRaw->value
            : $previousStatusRaw;

        app(InvoiceCallbackServiceContract::class)->send($invoice, 'status_changed');
        app(NotificationServiceContract::class)->dispatch(new InvoiceStatusChangedNotificationEvent($invoice, $previousStatus));
    }
}


