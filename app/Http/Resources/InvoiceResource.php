<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Contracts\Blockchain\ExplorerServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /** @var Invoice */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'telegram_url' => $this->bot_link_token && config('telegram.bots.mybot.username') ? 'https://t.me/'.ltrim(config('telegram.bots.mybot.username'), '@').'?start=pay_'.$this->bot_link_token : null,
            'external_invoice_id' => $this->external_invoice_id,
            'address_id' => $this->address_id,
            'address' => $this->whenLoaded('address', fn () => $this->address?->address),
            'payment_url' => route('invoices.public', $this->resource),
            'requested_amount' => $this->requested_amount ? app(MoneyServiceContract::class)->format(app(MoneyServiceContract::class)->fromMinor($this->requested_amount, $this->currency)) : null,
            'chain_network' => $this->chain_network,
            'amount' => app(MoneyServiceContract::class)->format($this->amount),
            'currency' => $this->currency->value,
            'currency_label' => strtoupper($this->currency->value),
            'network' => $this->network->value,
            'network_label' => strtoupper($this->network->value),
            'status' => $this->status->value,
            'txid' => $this->txid,
            'tx_explorer_url' => $this->txid ? app(ExplorerServiceContract::class)->getTransactionUrl($this->network, $this->currency, $this->txid) : null,
            'amount_received' => app(MoneyServiceContract::class)->format($this->amount_received),
            'confirmations' => $this->confirmations,
            'expires_at' => optional($this->expires_at)?->toISOString(),
            'callback_url' => $this->callback_url,
            'tag' => $this->tag,
            'metadata' => $this->metadata,
            'merchant_id' => $this->merchant_id,
            'merchant' => $this->whenLoaded('merchant', fn () => (new MerchantResource($this->merchant))->resolve()),
            'client_id' => $this->client_id,
            'client_external_id' => $this->client?->external_id,
            'client' => $this->whenLoaded('client', fn () => (new ClientResource($this->client))->resolve()),
            'product_name' => $this->product_name,
            'product_description' => $this->product_description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
