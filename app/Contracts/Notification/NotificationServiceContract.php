<?php

declare(strict_types=1);

namespace App\Contracts\Notification;

use App\Services\Notification\Events\NotificationEventInterface;

interface NotificationServiceContract
{
    public function dispatch(NotificationEventInterface $event): void;
}

