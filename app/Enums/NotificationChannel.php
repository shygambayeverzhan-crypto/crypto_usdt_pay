<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case IN_APP = 'in_app';
    case TELEGRAM = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::IN_APP => __('messages.notifications.channels.in_app'),
            self::TELEGRAM => __('messages.notifications.channels.telegram'),
        };
    }
}

