<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Contracts\Money\MoneyServiceContract;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin \App\Models\Address
 */
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency->value,
            'currency_label' => strtoupper($this->currency->value),
            'network' => $this->network->value,
            'network_label' => Str::upper($this->network->value),
            'address' => $this->address,
            'is_active' => (bool) $this->is_active,
            'balance' => app(MoneyServiceContract::class)->format($this->balance),
            'last_checked_at' => optional($this->last_checked_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}


