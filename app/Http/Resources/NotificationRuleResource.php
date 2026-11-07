<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Contracts\Money\MoneyServiceContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $money = app(MoneyServiceContract::class);
        $currency = $this->currency;

        return [
            'id' => $this->id,
            'event' => $this->event,
            'currency' => $currency?->value,
            'statuses' => $this->statuses ?? [],
            'channels' => $this->channels ?? [],
            'min_amount' => $currency ? $money->format($money->fromMinor($this->min_amount_minor, $currency)) : '0',
            'enabled' => (bool) $this->enabled,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

