<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Contracts\Notification\NotificationChannelContract;
use App\Models\Notification;

/**
 * Канал для внутренних уведомлений внутри панели.
 */
final class InAppNotificationChannel implements NotificationChannelContract
{
    public function send(Notification $notification): void
    {
        // Для внутренних уведомлений ничего отправлять не нужно,
        // запись уже создана в базе. Здесь можно расширить логику при необходимости.
    }
}

