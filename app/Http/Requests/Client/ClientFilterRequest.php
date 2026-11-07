<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $search Поиск по имени, external_id или контактам
 * @property-read bool|null $has_contact Наличие любого контакта
 */
class ClientFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'has_contact' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'has_contact' => $this->boolean('has_contact'),
        ];
    }
}


