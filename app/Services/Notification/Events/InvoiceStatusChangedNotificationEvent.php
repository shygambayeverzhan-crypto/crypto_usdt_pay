<?php

declare(strict_types=1);

namespace App\Services\Notification\Events;

use App\Enums\Currency;
use App\Enums\NotificationEvent;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Money\MoneyAmount;

final class InvoiceStatusChangedNotificationEvent implements NotificationEventInterface
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly ?string $previousStatus,
    ) {
    }

    public function type(): NotificationEvent
    {
        return NotificationEvent::INVOICE_STATUS_CHANGED;
    }

    public function user(): User
    {
        return $this->invoice->user;
    }

    public function currency(): ?Currency
    {
        return $this->invoice->currency;
    }

    public function amount(): ?MoneyAmount
    {
        return $this->invoice->amount;
    }

    public function status(): ?string
    {
        return $this->invoice->status?->value;
    }

    public function previousStatus(): ?string
    {
        return $this->previousStatus;
    }

    public function payload(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'status' => $this->status(),
            'previous_status' => $this->previousStatus,
            'external_invoice_id' => $this->invoice->external_invoice_id,
        ];
    }
}

