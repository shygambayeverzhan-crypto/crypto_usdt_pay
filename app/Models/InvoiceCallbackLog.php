<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceCallbackLog extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'id',
        'invoice_id',
        'event',
        'url',
        'request_payload',
        'response_status',
        'response_body',
        'error_message',
        'duration_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}


