<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\Network;
use App\Enums\NetworkCurrency;
use App\Contracts\Money\MoneyServiceContract;
use App\Services\Money\MoneyAmount;
use App\Services\Money\CurrencyAmountRulesService;
use App\Models\Merchant;
use App\Rules\CallbackUrlRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read string $currency ISO код валюты (например, USDT)
 * @property-read string $network Сеть (например, tron)
 * @property-read string $amount Сумма в десятичном виде
 * @property-read string|null $external_invoice_id Внешний ID
 * @property-read int $merchant_id Идентификатор мерчанта
 * @property-read string|null $product_name Название товара
 * @property-read string|null $product_description Описание товара
 * @property-read string|null $callback_url URL для callback
 * @property-read string|null $tag Произвольная метка
 * @property-read array|null $metadata Доп. данные
 * @property-read string|null $client_id Внешний идентификатор клиента
 * @property-read string|null $client_name Имя клиента
 * @property-read string|null $client_telegram Контакт Telegram
 * @property-read string|null $client_contact Дополнительный контакт
 */
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Нормализуем значения, чтобы валидация была предсказуемой и case-insensitive для currency/network.
        $currency = $this->input('currency');
        if ($currency !== null) {
            $this->merge(['currency' => strtolower(trim((string) $currency))]);
        }

        $network = $this->input('network');
        if ($network !== null) {
            $this->merge(['network' => strtolower(trim((string) $network))]);
        }

        $amount = $this->input('amount');
        if ($amount !== null) {
            $this->merge(['amount' => trim((string) $amount)]);
        }

        $callbackUrl = $this->input('callback_url');
        if ($callbackUrl !== null) {
            $trimmed = trim((string) $callbackUrl);
            $this->merge(['callback_url' => $trimmed === '' ? null : $trimmed]);
        }
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', new Enum(Currency::class)],
            'network' => ['required', 'string', new Enum(Network::class)],
            // Валидация формата по валюте — в withValidator(), т.к. зависит от currency
            'amount' => ['required', 'string', 'max:64'],
            'external_invoice_id' => ['nullable', 'string', 'max:64'],
            'merchant_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('merchants', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'product_name' => ['nullable', 'string', 'max:255'],
            'product_description' => ['nullable', 'string', 'max:2000'],
            'callback_url' => ['nullable', 'string', 'url', 'max:255', new CallbackUrlRule()],
            'tag' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'client_id' => ['nullable', 'string', 'max:128'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_telegram' => ['nullable', 'string', 'max:255'],
            'client_contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'merchant_id' => __('frontend.invoices.fields.merchant'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Если базовые правила уже упали — не продолжаем, чтобы не шуметь.
            if ($validator->errors()->has('currency') || $validator->errors()->has('amount')) {
                return;
            }

            $amount = trim((string) $this->input('amount', ''));
            if ($amount === '') {
                return; // required обработает
            }

            try {
                $currency = $this->toCurrencyEnum();
            } catch (\Throwable) {
                return; // currency обработается базовой валидацией
            }

            // Совместимость currency+network (поддерживаемые пары)
            try {
                $network = $this->toNetworkEnum();
                if (!NetworkCurrency::isSupported($currency, $network)) {
                    $validator->errors()->add('network', __('messages.addresses.errors.currency_mismatch_value', [
                        'currency' => $currency->value,
                        'network' => $network->value,
                    ]));
                    return;
                }
            } catch (\Throwable) {
                // network обработается базовыми правилами
            }

            $rules = app(CurrencyAmountRulesService::class);
            $regex = $rules->invoiceInputRegex($currency);
            $decimals = $rules->invoiceInputDecimals($currency);

            if (!preg_match($regex, $amount)) {
                $validator->errors()->add('amount', __('validation.app.invoice.amount_format', [
                    'currency' => $currency->value,
                    'decimals' => $decimals,
                ]));
                return;
            }

            // Дополнительно гарантируем, что сумма > 0 (диапазон min/max проверит InvoiceService).
            try {
                $money = app(MoneyServiceContract::class);
                $moneyAmount = $money->create($amount, $currency);
                $zero = $money->create('0', $currency);

                if ($money->compare($moneyAmount, $zero) <= 0) {
                    $validator->errors()->add('amount', __('validation.app.invoice.amount_positive'));
                }
            } catch (\Throwable) {
                // На всякий случай (неожиданное значение BigDecimal и т.п.)
                $validator->errors()->add('amount', __('validation.numeric'));
            }
        });
    }

    public function toCurrencyEnum(): Currency
    {
        return Currency::from(strtolower(trim((string) $this->input('currency'))));
    }

    public function toNetworkEnum(): Network
    {
        return Network::from(strtolower(trim((string) $this->input('network'))));
    }

    public function toMoneyAmount(MoneyServiceContract $money): MoneyAmount
    {
        return $money->create($this->input('amount'), $this->toCurrencyEnum());
    }

    public function clientExternalId(): ?string
    {
        $value = $this->input('client_id');
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    public function clientName(): ?string
    {
        return $this->nullableString('client_name');
    }

    public function clientTelegram(): ?string
    {
        return $this->nullableString('client_telegram');
    }

    public function clientContact(): ?string
    {
        return $this->nullableString('client_contact');
    }

    public function merchant(): ?Merchant
    {
        $merchantId = $this->input('merchant_id');
        if (!$merchantId) {
            return null;
        }

        return Merchant::query()
            ->where('id', (int) $merchantId)
            ->where('user_id', $this->user()?->id)
            ->first();
    }

    public function productName(): ?string
    {
        $value = $this->input('product_name');
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : null;
    }

    public function productDescription(): ?string
    {
        $value = $this->input('product_description');
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : null;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->input($key);
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}


