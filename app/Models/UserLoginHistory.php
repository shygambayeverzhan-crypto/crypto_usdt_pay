<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends Model
{
    use HasFactory;

    protected $table = 'user_login_histories';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'platform',
        'browser',
        'geo_status',
        'geo',
    ];

    protected $casts = [
        'geo' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

