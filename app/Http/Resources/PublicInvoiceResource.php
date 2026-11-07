<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PublicInvoiceResource extends InvoiceResource
{
    public function toArray(Request $request): array
    {
        return array_intersect_key(parent::toArray($request), array_flip([
            'id', 'address', 'payment_url', 'amount', 'requested_amount', 'currency', 'currency_label',
            'network', 'network_label', 'chain_network', 'status', 'txid', 'tx_explorer_url',
            'amount_received', 'confirmations', 'expires_at', 'product_name', 'product_description',
        ]));
    }
}
