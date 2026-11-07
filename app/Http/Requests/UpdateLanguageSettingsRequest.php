<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\LocaleOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read array|null $locales Список кодов языков, которые нужно оставить
 */
class UpdateLanguageSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locales' => ['nullable', 'array'],
            'locales.*' => ['required', 'string', 'distinct', Rule::in(LocaleOptions::codes())],
        ];
    }
}


