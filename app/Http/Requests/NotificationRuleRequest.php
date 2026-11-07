<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Contracts\Money\MoneyServiceContract;
use App\Services\Money\MoneyAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $event Тип события
 * @property-read string $currency Валюта фильтра
 * @property-read array<int,string>|null $statuses Список статусов для фильтрации
 * @property-read array<int,string> $channels Каналы доставки
 * @property-read string|int|float $min_amount Минимальная сумма (decimal)
 * @property-read bool|null $enabled Флаг активности
 */
class NotificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::in([
                NotificationEvent::INVOICE_CREATED->value,
                NotificationEvent::INVOICE_STATUS_CHANGED->value,
            ])],
            'currency' => ['required', 'string'],
            'statuses' => [
                Rule::requiredIf(fn () => $this->shouldRequireStatuses()),
                'nullable',
                'array',
            ],
            'statuses.*' => ['string', 'max:32'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', 'max:32', Rule::in(
                collect(NotificationChannel::cases())->map(fn ($channel) => $channel->value)->all()
            )],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    public function toCurrencyEnum(): Currency
    {
        return Currency::from(strtolower((string) $this->input('currency')));
    }

    public function toMoneyAmount(MoneyServiceContract $money): MoneyAmount
    {
        return $money->create($this->input('min_amount'), $this->toCurrencyEnum());
    }

    public function statuses(): array
    {
        $statuses = $this->input('statuses');
        if (!is_array($statuses)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($status) => (string) $status, $statuses)));
    }

    public function channels(): array
    {
        $channels = $this->input('channels');
        if (!is_array($channels)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($channel) => (string) $channel, $channels)));
    }

    public function isEnabled(): bool
    {
        if ($this->has('enabled')) {
            return $this->boolean('enabled');
        }

        return true;
    }

    private function shouldRequireStatuses(): bool
    {
        return $this->input('event') === NotificationEvent::INVOICE_STATUS_CHANGED->value;
    }
}

