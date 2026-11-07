<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Currency;
use App\Contracts\Money\MoneyServiceContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MoneyServiceContract $money */
        $money = app(MoneyServiceContract::class);
        $currency = Currency::from((string) $this->currency);

        $min = $money->fromMinor($this->min_invoice_amount_minor, $currency);
        $max = $money->fromMinor($this->max_invoice_amount_minor, $currency);

        return [
            'id' => $this->id,
            'currency' => $currency->value,
            'min_invoice_amount' => (float) $money->format($min),
            'max_invoice_amount' => (float) $money->format($max),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}


