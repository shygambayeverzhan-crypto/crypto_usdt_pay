<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $status_group
 * @property-read string|null $event
 * @property-read string|null $invoice_id
 * @property-read bool|null $has_error
 */
class CallbackLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status_group' => ['nullable', 'string', Rule::in(['success', 'error'])],
            'event' => ['nullable', 'string', 'max:255'],
            'invoice_id' => ['nullable', 'string', 'max:255'],
            'has_error' => ['sometimes', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => (string) $this->input('search', ''),
            'status_group' => (string) $this->input('status_group', ''),
            'event' => (string) $this->input('event', ''),
            'invoice_id' => (string) $this->input('invoice_id', ''),
            'has_error' => $this->boolean('has_error'),
        ];
    }
}


