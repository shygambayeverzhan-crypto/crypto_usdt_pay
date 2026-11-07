<?php

declare(strict_types=1);

namespace App\Casts;

use App\Contracts\Money\MoneyServiceContract;
use App\Services\Money\MoneyAmount;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class MoneyAmountCast implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes): ?MoneyAmount
    {
        if ($value === null) {
            return null;
        }

        $currency = $model->currency; // Enum cast присутствует в модели
        /** @var MoneyServiceContract $money */
        $money = app(MoneyServiceContract::class);
        return $money->fromMinor($value, $currency);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof MoneyAmount) {
            /** @var MoneyServiceContract $money */
            $money = app(MoneyServiceContract::class);
            return $money->toMinor($value);
        }

        // Если пришла строка/число — сохраняем как есть (ожидается minor-строка)
        return (string) $value;
    }
}


