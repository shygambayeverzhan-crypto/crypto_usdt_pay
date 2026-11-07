<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    public const NAME_DEFAULT = 'Default';

    public const NAME_CALLBACK = 'Callback';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function allowedIps(): HasMany
    {
        return $this->hasMany(ApiTokenAllowedIp::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }
}
