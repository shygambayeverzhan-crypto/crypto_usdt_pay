<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event',
        'currency',
        'statuses',
        'channels',
        'min_amount_minor',
        'enabled',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'statuses' => 'array',
        'channels' => 'array',
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

