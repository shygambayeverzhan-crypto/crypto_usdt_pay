<?php

declare(strict_types=1);

namespace App\Services\Notification\Templates;

final class NotificationContent
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $payload = [],
    ) {
    }
}

