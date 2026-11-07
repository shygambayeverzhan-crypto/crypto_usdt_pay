<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event',
        'channel',
        'status',
        'title',
        'body',
        'payload',
        'error_message',
        'read_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'channel' => NotificationChannel::class,
        'status' => NotificationDeliveryStatus::class,
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->read_at = now();
        $this->save();
    }
}

