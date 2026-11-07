<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use App\Enums\Currency;
use App\Enums\Network;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $currency
 * @property-read string|null $network
 * @property-read bool|null $is_active
 */
class AddressFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', Rule::in(array_map(static fn (Currency $c) => $c->value, Currency::cases()))],
            'network' => ['nullable', 'string', Rule::in(array_map(static fn (Network $n) => $n->value, Network::cases()))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'currency' => (string) $this->input('currency', ''),
            'network' => (string) $this->input('network', ''),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}


