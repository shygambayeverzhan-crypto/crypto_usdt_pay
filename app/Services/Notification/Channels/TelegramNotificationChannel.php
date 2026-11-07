<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Contracts\Notification\NotificationChannelContract;
use App\Contracts\Telegram\TelegramServiceContract;
use App\Models\Notification;

final class TelegramNotificationChannel implements NotificationChannelContract
{
    public function __construct(
        private readonly TelegramServiceContract $telegram,
    ) {
    }

    public function send(Notification $notification): void
    {
        $this->telegram->sendNotification($notification);
    }
}

