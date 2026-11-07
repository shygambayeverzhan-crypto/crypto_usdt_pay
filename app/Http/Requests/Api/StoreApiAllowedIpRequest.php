<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\ApiToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreApiAllowedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tokenId = (int) $this->input('api_token_id');

        return ApiToken::query()
            ->where('id', $tokenId)
            ->where('user_id', Auth::id())
            ->exists();
    }

    public function rules(): array
    {
        $tokenId = (int) $this->input('api_token_id');

        return [
            'api_token_id' => [
                'required',
                'integer',
                Rule::exists('api_tokens', 'id')->where('user_id', Auth::id()),
            ],
            'ip' => [
                'required',
                'string',
                'ip',
                'max:45',
                Rule::unique('api_token_allowed_ips', 'ip')->where('api_token_id', $tokenId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ip' => trim((string) $this->input('ip')),
        ]);
    }
}


