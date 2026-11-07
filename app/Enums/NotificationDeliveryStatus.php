<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case PENDING = 'pending';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('messages.notifications.delivery_statuses.pending'),
            self::DELIVERED => __('messages.notifications.delivery_statuses.delivered'),
            self::FAILED => __('messages.notifications.delivery_statuses.failed'),
        };
    }
}

