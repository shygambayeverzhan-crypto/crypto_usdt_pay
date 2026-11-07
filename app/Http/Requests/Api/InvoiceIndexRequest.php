<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search Строка поиска (id, external_invoice_id, client, tag, address)
 * @property-read string|null $status Статус инвойса
 * @property-read string|null $currency Код валюты (например, USDT)
 * @property-read string|null $network Сеть (например, tron)
 * @property-read int|null $merchant_id ID мерчанта
 * @property-read string|null $client_id Client ID клиента (external_id)
 * @property-read string|null $external_invoice_id Внешний ID инвойса
 * @property-read string|null $tag Метка инвойса
 * @property-read bool|null $has_callback Только инвойсы с callback_url
 * @property-read int|null $page Номер страницы
 * @property-read int|null $per_page Количество на страницу
 */
class InvoiceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_map(static fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases()))],
            'currency' => ['nullable', 'string', Rule::in(array_map(static fn (Currency $currency) => $currency->value, Currency::cases()))],
            'network' => ['nullable', 'string', Rule::in(array_map(static fn (Network $network) => $network->value, Network::cases()))],
            'merchant_id' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['nullable', 'string', 'max:128'],
            'external_invoice_id' => ['nullable', 'string', 'max:64'],
            'tag' => ['nullable', 'string', 'max:255'],
            'has_callback' => ['sometimes', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->trimmed('search'),
            'status' => $this->normalizedLower('status'),
            'currency' => $this->normalizedUpper('currency'),
            'network' => $this->normalizedLower('network'),
            'merchant_id' => $this->filled('merchant_id') ? (int) $this->integer('merchant_id') : null,
            'client_external_id' => $this->trimmed('client_id'),
            'external_invoice_id' => $this->trimmed('external_invoice_id'),
            'tag' => $this->trimmed('tag'),
            'has_callback' => $this->boolean('has_callback'),
        ];
    }

    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', 20);

        return min(max($perPage, 1), 100);
    }

    private function trimmed(string $key): ?string
    {
        $value = $this->input($key);
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizedUpper(string $key): ?string
    {
        $value = $this->trimmed($key);

        return $value === null ? null : strtoupper($value);
    }

    private function normalizedLower(string $key): ?string
    {
        $value = $this->trimmed($key);

        return $value === null ? null : strtolower($value);
    }
}


