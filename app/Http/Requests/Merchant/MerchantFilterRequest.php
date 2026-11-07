<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $search
 * @property-read bool|null $white_label_enabled
 */
class MerchantFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'white_label_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'white_label_enabled' => $this->boolean('white_label_enabled'),
        ];
    }
}


