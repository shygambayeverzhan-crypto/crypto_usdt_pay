<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTokenAllowedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_token_id',
        'ip',
    ];

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }
}


