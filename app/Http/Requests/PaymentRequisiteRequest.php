<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PaymentRequisite;
use App\Services\Blockchain\TronAddress;
use App\Support\PaymentRequisiteOptions;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class PaymentRequisiteRequest extends FormRequest
{
    private const AMOUNT_PATTERN = '/^\d{1,12}(?:\.\d{1,6})?$/D';

    private const FORM_FIELDS = [
        'label', 'method', 'currency', 'bank', 'bank_name', 'holder', 'number',
        'address', 'network', 'min_amount', 'max_amount', 'liquidity_limit',
    ];

    public function authorize(): bool
    {
        $requisite = $this->existingRequisite();
        if ($requisite !== null && (int) $requisite->user_id !== (int) $this->user()?->id) {
            abort(404);
        }

        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('number'))) {
            $this->merge(['number' => preg_replace('/\s+/u', '', $this->input('number'))]);
        }

        foreach (['min_amount', 'max_amount', 'liquidity_limit'] as $field) {
            $amount = $this->input($field);
            if (is_string($amount)) {
                $normalized = preg_replace('/[\s\p{Z}]+/u', '', str_replace(',', '.', $amount));
                $this->merge([$field => $normalized === '' ? null : $normalized]);
            }
        }
    }

    public function rules(): array
    {
        if ($this->isStatusOnly()) {
            return ['is_active' => ['required', 'boolean']];
        }

        $isCard = $this->input('method') === 'card';
        $isUsdt = $this->input('method') === 'usdt';
        $needsNumber = $isCard && $this->existingRequisite()?->method !== 'card';

        return [
            'label' => ['required', 'string', 'max:80'],
            'method' => ['required', Rule::in(['card', 'usdt'])],
            'currency' => ['required', Rule::in($isCard ? PaymentRequisiteOptions::CARD_CURRENCIES : ['USDT'])],
            'bank' => [Rule::excludeIf(! $isCard), 'required', Rule::in(array_keys(PaymentRequisiteOptions::BANKS))],
            'bank_name' => [Rule::excludeIf(! $isCard || $this->input('bank') !== 'other'), 'required', 'string', 'max:100'],
            'holder' => [Rule::excludeIf(! $isCard), 'required', 'string', 'max:120'],
            'number' => [Rule::excludeIf(! $isCard), 'bail', Rule::requiredIf($needsNumber), 'nullable', 'string', 'regex:/^[0-9]{12,19}$/D', $this->cardNumberRule()],
            'network' => [Rule::excludeIf(! $isUsdt), 'required', Rule::in(array_keys(PaymentRequisiteOptions::NETWORKS))],
            'address' => [Rule::excludeIf(! $isUsdt), 'bail', 'required', 'string', 'max:100', $this->addressRule()],
            'min_amount' => ['bail', 'required', 'string', 'regex:'.self::AMOUNT_PATTERN, $this->amountRule(false)],
            'max_amount' => ['bail', 'required', 'string', 'regex:'.self::AMOUNT_PATTERN, $this->amountRule(false)],
            'liquidity_limit' => ['bail', 'nullable', 'string', 'regex:'.self::AMOUNT_PATTERN, $this->amountRule(true)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->isStatusOnly() || $validator->errors()->has('min_amount') || $validator->errors()->has('max_amount')) {
                return;
            }

            if (BigDecimal::of($this->input('max_amount'))->isLessThan(BigDecimal::of($this->input('min_amount')))) {
                $validator->errors()->add('max_amount', $this->text('Максимальная сумма должна быть не меньше минимальной.', 'The maximum amount must be at least the minimum amount.'));
            }
        }];
    }

    public function persistenceData(): array
    {
        $data = $this->validated();
        if ($this->isStatusOnly()) {
            return ['is_active' => (bool) $data['is_active']];
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? $this->existingRequisite()?->is_active ?? false);
        $data['liquidity_limit'] = $data['liquidity_limit'] ?? null;

        if ($data['method'] === 'card') {
            $data['bank_name'] = $data['bank'] === 'other' ? $data['bank_name'] : null;
            $data['network'] = null;
            $data['address'] = null;
            if (! empty($data['number'])) {
                $data['last4'] = substr($data['number'], -4);
            } else {
                // An empty edit field keeps the encrypted PAN; never send it back to the browser.
                unset($data['number']);
            }
        } else {
            $data['bank'] = null;
            $data['bank_name'] = null;
            $data['holder'] = null;
            $data['number'] = null;
            $data['last4'] = null;
        }

        return $data;
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        // Inertia preserves the form locally. Do not flash card details into database sessions.
        throw new HttpResponseException(redirect()->route('requisites.index')->withErrors($validator, $this->errorBag));
    }

    private function existingRequisite(): ?PaymentRequisite
    {
        $requisite = $this->route('requisite');

        return $requisite instanceof PaymentRequisite ? $requisite : null;
    }

    private function isStatusOnly(): bool
    {
        return $this->isMethod('patch') && $this->has('is_active') && ! $this->hasAny(self::FORM_FIELDS);
    }

    private function amountRule(bool $allowZero): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowZero): void {
            $comparison = BigDecimal::of($value)->compareTo(BigDecimal::zero());
            if ($comparison < 0 || (! $allowZero && $comparison === 0)) {
                $fail($this->text('Укажите положительную сумму.', 'Enter a positive amount.'));
            }
        };
    }

    private function cardNumberRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $sum = 0;
            $double = false;
            for ($index = strlen($value) - 1; $index >= 0; $index--) {
                $digit = (int) $value[$index];
                if ($double) {
                    $digit *= 2;
                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }
                $sum += $digit;
                $double = ! $double;
            }
            if ($sum === 0 || $sum % 10 !== 0) {
                $fail($this->text('Проверьте номер карты.', 'Check the card number.'));
            }
        };
    }

    private function addressRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $network = $this->input('network');
            if ($network === 'tron') {
                try {
                    TronAddress::hex($value);
                } catch (InvalidArgumentException) {
                    $fail($this->text('Укажите корректный адрес TRON.', 'Enter a valid TRON address.'));
                }
            } elseif (in_array($network, ['ethereum', 'bsc'], true)) {
                if (! preg_match('/^0x[0-9a-fA-F]{40}$/D', $value) || strtolower($value) === '0x'.str_repeat('0', 40)) {
                    $fail($this->text('Укажите корректный адрес выбранной сети.', 'Enter a valid address for the selected network.'));
                }
            }
        };
    }

    private function text(string $russian, string $english): string
    {
        return app()->getLocale() === 'ru' ? $russian : $english;
    }
}
