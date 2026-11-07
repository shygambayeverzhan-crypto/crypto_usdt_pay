<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $status
 * @property-read string|null $currency
 * @property-read string|null $network
 * @property-read int|null $merchant_id
 * @property-read string|null $client_id
 * @property-read bool|null $has_callback
 */
class InvoiceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_map(static fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases()))],
            'currency' => ['nullable', 'string', Rule::in(array_map(static fn (Currency $currency) => $currency->value, Currency::cases()))],
            'network' => ['nullable', 'string', Rule::in(array_map(static fn (Network $network) => $network->value, Network::cases()))],
            'merchant_id' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['nullable', 'string', 'max:26'],
            'has_callback' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'status' => (string) $this->input('status', ''),
            'currency' => (string) $this->input('currency', ''),
            'network' => (string) $this->input('network', ''),
            'merchant_id' => $this->filled('merchant_id') ? (string) $this->integer('merchant_id') : '',
            'client_id' => (string) $this->input('client_id', ''),
            'has_callback' => $this->boolean('has_callback'),
        ];
    }
}


