<?php

declare(strict_types=1);

namespace App\Contracts\Notification;

use App\Models\Notification;

interface NotificationChannelContract
{
    public function send(Notification $notification): void;
}

