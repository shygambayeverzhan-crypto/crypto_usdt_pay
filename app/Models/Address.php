<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\Network;
use App\Casts\MoneyAmountCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'network',
        'address',
        'is_active',
        'balance',
        'last_checked_at',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'network' => Network::class,
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'balance' => MoneyAmountCast::class,
    ];

    protected $attributes = [
        'is_active' => true,
        'balance' => '0',
        'last_checked_at' => null,
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


