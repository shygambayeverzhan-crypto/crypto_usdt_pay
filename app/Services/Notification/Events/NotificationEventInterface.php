<?php

declare(strict_types=1);

namespace App\Services\Notification\Events;

use App\Enums\Currency;
use App\Enums\NotificationEvent;
use App\Models\User;
use App\Services\Money\MoneyAmount;

interface NotificationEventInterface
{
    public function type(): NotificationEvent;

    public function user(): User;

    public function currency(): ?Currency;

    public function amount(): ?MoneyAmount;

    public function status(): ?string;

    public function previousStatus(): ?string;

    public function payload(): array;
}

