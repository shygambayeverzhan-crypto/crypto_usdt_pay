<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read bool $approved Одобрен ли пользователь
 */
class UpdateUserApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'approved' => ['required', 'boolean'],
        ];
    }
}


