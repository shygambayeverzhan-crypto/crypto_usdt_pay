<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read int|null $page Номер страницы
 * @property-read int|null $per_page Кол-во на страницу
 */
class ClientIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', 20);

        return min(max($perPage, 1), 100);
    }
}


