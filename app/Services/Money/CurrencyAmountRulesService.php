<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;

/**
 * Единый источник правил формата ввода суммы (invoice amount) по валютам.
 *
 * Используется:
 * - на бэкенде: для валидации формата (кол-во знаков после точки)
 * - на фронтенде: для форматирования/ограничения ввода (через Inertia shared props)
 */
final class CurrencyAmountRulesService
{
    public function __construct(
        private readonly AppSettingsServiceContract $appSettings,
        private readonly MoneyServiceContract $money,
    ) {
    }

    public function invoiceInputDecimals(Currency $currency): int
    {
        $value = config("currency_amount_rules.{$currency->value}.invoice_decimals");
        if ($value === null) {
            $value = config('currency_amount_rules.default.invoice_decimals', 6);
        }

        return max(0, (int) $value);
    }

    /**
     * Regex для суммы в десятичном виде (только цифры + опциональная точка).
     * Пример для USDT (2): /^\d+(?:\.\d{1,2})?$/
     */
    public function invoiceInputRegex(Currency $currency): string
    {
        $decimals = $this->invoiceInputDecimals($currency);

        if ($decimals <= 0) {
            return '/^\d+$/';
        }

        return '/^\d+(?:\.\d{1,' . $decimals . '})?$/';
    }

    public function decimalSeparator(Currency $currency): string
    {
        $value = config("currency_amount_rules.{$currency->value}.decimal_separator");
        if ($value === null) {
            $value = config('currency_amount_rules.default.decimal_separator', '.');
        }

        $value = (string) $value;
        return $value !== '' ? $value : '.';
    }

    public function example(Currency $currency): string
    {
        $value = config("currency_amount_rules.{$currency->value}.example");
        if ($value === null) {
            $value = config('currency_amount_rules.default.example', '0.00');
        }

        $value = trim((string) $value);
        return $value !== '' ? $value : '0.00';
    }

    /**
     * Данные для фронтенда (только JSON-serializable значения).
     *
     * @return array<string, array{decimals:int, example:string, decimal_separator:string, min:?string, max:?string}>
     */
    public function frontendRules(): array
    {
        $result = [];

        foreach (Currency::cases() as $currency) {
            $settings = $this->appSettings->get($currency);
            $min = null;
            $max = null;

            if ($settings !== null) {
                $minMoney = $this->money->fromMinor($settings->min_invoice_amount_minor, $currency);
                $maxMoney = $this->money->fromMinor($settings->max_invoice_amount_minor, $currency);
                $min = $this->money->format($minMoney);
                $max = $this->money->format($maxMoney);
            }

            $result[$currency->value] = [
                'decimals' => $this->invoiceInputDecimals($currency),
                'example' => $this->example($currency),
                'decimal_separator' => $this->decimalSeparator($currency),
                'min' => $min,
                'max' => $max,
            ];
        }

        return $result;
    }
}


