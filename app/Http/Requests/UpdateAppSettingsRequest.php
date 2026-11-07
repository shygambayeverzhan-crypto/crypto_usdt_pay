<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Contracts\Money\MoneyServiceContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read array $settings Массив объектов { currency: string, min_invoice_amount: string|number, max_invoice_amount: string|number }
 */
class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.currency' => ['required', 'string'],
            'settings.*.min_invoice_amount' => ['required', 'numeric', 'min:0'],
            'settings.*.max_invoice_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $money = app(MoneyServiceContract::class);
            $maxUsdtValue = $this->getMaxUsdtValueInMinor($money);

            foreach ($this->input('settings', []) as $index => $item) {
                if (!isset($item['currency']) || !isset($item['min_invoice_amount']) || !isset($item['max_invoice_amount'])) {
                    continue;
                }

                try {
                    $currency = Currency::from(strtolower((string) $item['currency']));

                    $min = $money->create($item['min_invoice_amount'], $currency);
                    $max = $money->create($item['max_invoice_amount'], $currency);

                    $minMinorStr = $money->toMinor($min);
                    $maxMinorStr = $money->toMinor($max);

                    if (!preg_match('/^-?\d+$/', $minMinorStr)) {
                        $validator->errors()->add(
                            "settings.{$index}.min_invoice_amount",
                            __('validation.app.settings.min_integer')
                        );
                        continue;
                    }

                    if (!preg_match('/^-?\d+$/', $maxMinorStr)) {
                        $validator->errors()->add(
                            "settings.{$index}.max_invoice_amount",
                            __('validation.app.settings.max_integer')
                        );
                        continue;
                    }

                    $minMinor = (int) $minMinorStr;
                    $maxMinor = (int) $maxMinorStr;

                    if ($minMinor < 0) {
                        $validator->errors()->add(
                            "settings.{$index}.min_invoice_amount",
                            __('validation.app.settings.min_non_negative')
                        );
                    }

                    if ($maxMinor < 0) {
                        $validator->errors()->add(
                            "settings.{$index}.max_invoice_amount",
                            __('validation.app.settings.max_non_negative')
                        );
                    }

                    if ($maxMinor > $maxUsdtValue) {
                        $maxUsdtFormatted = $money->format($money->fromMinor($maxUsdtValue, Currency::USDT));
                        $validator->errors()->add(
                            "settings.{$index}.max_invoice_amount",
                            __('validation.app.settings.max_limit_exceeded', ['amount' => $maxUsdtFormatted])
                        );
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки конвертации, они будут обработаны другими правилами валидации
                }
            }
        });
    }

    private function getMaxUsdtValueInMinor(MoneyServiceContract $money): int
    {
        $maxUsdt = $money->create('9223372036854775807', Currency::USDT);
        return (int) $money->toMinor($maxUsdt);
    }

    /** Преобразовать входные суммы в минорные единицы по валютам */
    public function toMinorMap(MoneyServiceContract $money): array
    {
        $result = [];
        foreach ((array) $this->input('settings', []) as $item) {
            $currency = Currency::from(strtolower((string) ($item['currency'] ?? '')));
            $min = $money->create($item['min_invoice_amount'], $currency);
            $max = $money->create($item['max_invoice_amount'], $currency);
            $result[] = [
                'currency' => $currency,
                'min_minor' => $money->toMinor($min),
                'max_minor' => $money->toMinor($max),
            ];
        }
        return $result;
    }
}


