<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'initials',
        'logo_path',
        'back_url',
        'white_label_enabled',
        'invoice_expires_in_minutes',
    ];

    protected $casts = [
        'white_label_enabled' => 'boolean',
        'invoice_expires_in_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

