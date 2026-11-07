<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'link_token',
        'is_active',
        'linked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'linked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

