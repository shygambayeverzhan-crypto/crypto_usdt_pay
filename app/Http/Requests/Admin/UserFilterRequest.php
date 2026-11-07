<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $role
 */
class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'user'])],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'role' => (string) $this->input('role', ''),
        ];
    }
}


