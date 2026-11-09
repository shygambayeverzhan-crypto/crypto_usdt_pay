<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequisiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'method' => $this->method,
            'currency' => $this->currency,
            'bank' => $this->bank,
            'bank_name' => $this->bank_name,
            'holder' => $this->method === 'card' ? $this->holder : null,
            'number_masked' => $this->method === 'card' && $this->last4 ? '•••• '.$this->last4 : null,
            'address' => $this->address,
            'network' => $this->network,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'liquidity_limit' => $this->liquidity_limit,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
