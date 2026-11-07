<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $name Имя пользователя
 * @property-read string $email E-mail пользователя
 * @property-read string|null $password Новый пароль
 * @property-read string|null $password_confirmation Подтверждение пароля
 * @property-read string|null $role Роль пользователя (admin|user)
 * @property-read bool|null $approved Одобрен ли пользователь
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'user'])],
            'approved' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => trim((string) $this->input('email')),
            'role' => $this->filled('role') ? trim((string) $this->input('role')) : null,
        ]);
    }
}


