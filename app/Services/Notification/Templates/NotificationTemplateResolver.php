<?php

declare(strict_types=1);

namespace App\Services\Notification\Templates;

use App\Contracts\Money\MoneyServiceContract;
use App\Enums\NotificationEvent;
use App\Enums\NotificationChannel;
use App\Services\Notification\Events\NotificationEventInterface;

final class NotificationTemplateResolver
{
    public function __construct(
        private readonly MoneyServiceContract $money,
    ) {
    }

    public function build(NotificationEventInterface $event, NotificationChannel $channel): NotificationContent
    {
        return match ($event->type()) {
            NotificationEvent::INVOICE_CREATED => $this->invoiceCreated($event, $channel),
            NotificationEvent::INVOICE_STATUS_CHANGED => $this->invoiceStatusChanged($event, $channel),
        };
    }

    private function invoiceCreated(NotificationEventInterface $event, NotificationChannel $channel): NotificationContent
    {
        $currency = $event->currency();
        $amount = $event->amount();
        $formattedAmount = $amount && $currency
            ? $this->money->format($amount)
            : '0';

        $title = __('messages.notifications.templates.invoice_created.title');

        $body = __('messages.notifications.templates.invoice_created.body', [
            'amount' => $formattedAmount,
            'currency' => $currency?->value ?? '',
        ]);

        return new NotificationContent($title, $body, $event->payload());
    }

    private function invoiceStatusChanged(NotificationEventInterface $event, NotificationChannel $channel): NotificationContent
    {
        $currency = $event->currency();
        $amount = $event->amount();
        $formattedAmount = $amount && $currency
            ? $this->money->format($amount)
            : '0';

        $title = __('messages.notifications.templates.invoice_status_changed.title');

        $body = __('messages.notifications.templates.invoice_status_changed.body', [
            'status' => $event->status() ?? '',
            'previous' => $event->previousStatus() ?? '',
            'amount' => $formattedAmount,
            'currency' => $currency?->value ?? '',
        ]);

        return new NotificationContent($title, $body, $event->payload());
    }
}

