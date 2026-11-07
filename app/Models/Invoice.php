<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyAmountCast;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Observers\InvoiceObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'id',
        'user_id',
        'merchant_id',
        'client_id',
        'external_invoice_id',
        'address_id',
        'amount',
        'currency',
        'network',
        'status',
        'txid',
        'amount_received',
        'confirmations',
        'expires_at',
        'callback_url',
        'tag',
        'metadata',
        'product_name',
        'product_description',
    ];

    protected $casts = [
        'amount' => MoneyAmountCast::class,
        'amount_received' => MoneyAmountCast::class,
        'currency' => Currency::class,
        'network' => Network::class,
        'status' => InvoiceStatus::class,
        'confirmations' => 'integer',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'amount_received' => '0',
        'confirmations' => 0,
        'metadata' => '[]',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function callbackLogs(): HasMany
    {
        return $this->hasMany(InvoiceCallbackLog::class);
    }
}


