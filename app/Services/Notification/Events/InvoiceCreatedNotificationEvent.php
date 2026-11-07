<?php

declare(strict_types=1);

namespace App\Services\Notification\Events;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationEvent;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Money\MoneyAmount;

final class InvoiceCreatedNotificationEvent implements NotificationEventInterface
{
    public function __construct(
        private readonly Invoice $invoice,
    ) {
    }

    public function type(): NotificationEvent
    {
        return NotificationEvent::INVOICE_CREATED;
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
        return $this->invoice->status?->value ?? InvoiceStatus::PENDING->value;
    }

    public function previousStatus(): ?string
    {
        return null;
    }

    public function payload(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'status' => $this->status(),
            'external_invoice_id' => $this->invoice->external_invoice_id,
        ];
    }
}

