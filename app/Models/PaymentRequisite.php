<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequisite extends Model
{
    protected $fillable = [
        'label', 'method', 'currency', 'bank', 'bank_name', 'holder', 'number',
        'last4', 'address', 'network', 'min_amount', 'max_amount', 'liquidity_limit', 'is_active',
    ];

    protected $hidden = ['number', 'holder'];

    protected $attributes = ['is_active' => false];

    protected function casts(): array
    {
        return [
            'number' => 'encrypted',
            'holder' => 'encrypted',
            'min_amount' => 'decimal:6',
            'max_amount' => 'decimal:6',
            'liquidity_limit' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
