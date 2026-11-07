<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationEvent: string
{
    case INVOICE_CREATED = 'invoice.created';
    case INVOICE_STATUS_CHANGED = 'invoice.status_changed';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE_CREATED => __('messages.notifications.events.invoice_created'),
            self::INVOICE_STATUS_CHANGED => __('messages.notifications.events.invoice_status_changed'),
        };
    }
}

