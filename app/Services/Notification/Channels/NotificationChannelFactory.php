<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Contracts\Notification\NotificationChannelContract;
use App\Enums\NotificationChannel;
use App\Services\Notification\Exceptions\NotificationChannelNotFound;
use App\Services\Notification\Channels\TelegramNotificationChannel;

final class NotificationChannelFactory
{
    public function make(NotificationChannel $channel): NotificationChannelContract
    {
        return match ($channel) {
            NotificationChannel::IN_APP => app(InAppNotificationChannel::class),
            NotificationChannel::TELEGRAM => app(TelegramNotificationChannel::class),
            default => throw new NotificationChannelNotFound($channel->value),
        };
    }
}

