<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $name Имя пользователя
 * @property-read string $email E-mail пользователя
 * @property-read string $password Пароль
 * @property-read string $password_confirmation Подтверждение пароля
 * @property-read string $role Роль пользователя (admin|user)
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'role' => ['required', 'string', Rule::in(['admin', 'user'])],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => trim((string) $this->input('email')),
            'role' => trim((string) $this->input('role')),
        ]);
    }
}


