<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $external_id Внешний идентификатор клиента
 * @property-read string|null $name Имя клиента
 * @property-read string|null $telegram Контакт в Telegram
 * @property-read string|null $contact Дополнительный контакт
 */
class ClientUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'external_id' => [
                'required',
                'string',
                'max:128',
                Rule::unique('clients', 'external_id')
                    ->ignore($clientId, 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function externalId(): string
    {
        return trim((string) $this->input('external_id'));
    }

    public function clientName(): ?string
    {
        return $this->nullableString('name');
    }

    public function clientTelegram(): ?string
    {
        return $this->nullableString('telegram');
    }

    public function clientContact(): ?string
    {
        return $this->nullableString('contact');
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->input($key);
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}


