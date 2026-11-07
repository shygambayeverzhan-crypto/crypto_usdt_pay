<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class MerchantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $backUrlRules = ['nullable', 'string', 'max:2048', 'url'];

        if (app()->isLocal()) {
            $backUrlRules[] = 'starts_with:http://,https://';
        } else {
            $backUrlRules[] = 'starts_with:https://';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'initials' => ['required', 'string', 'max:16'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=500,max_height=500'],
            'back_url' => $backUrlRules,
            'white_label_enabled' => ['sometimes', 'boolean'],
            'invoice_expires_in_minutes' => ['sometimes', 'integer', 'min:1', 'max:10080'],
        ];
    }
}

