<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageSetting extends Model
{
    use HasFactory;

    protected $table = 'language_settings';

    protected $fillable = [
        'enabled_locales',
    ];

    protected $casts = [
        'enabled_locales' => 'array',
    ];
}


