<?php

declare(strict_types=1);

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string'],
            'network' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }
}


