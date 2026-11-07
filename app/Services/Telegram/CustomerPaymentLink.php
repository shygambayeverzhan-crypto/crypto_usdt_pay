<?php

namespace App\Services\Telegram;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class CustomerPaymentLink
{
    public function bind(string $token, string $chatId): ?Invoice
    {
        if (! preg_match('/^[A-Za-z0-9]{40}$/D', $token)) {
            return null;
        }

        return DB::transaction(function () use ($token, $chatId) {
            $invoice = Invoice::query()->where('bot_link_token', $token)->lockForUpdate()->first();
            if (! $invoice || ($invoice->customer_telegram_chat_id && $invoice->customer_telegram_chat_id !== $chatId)) {
                return null;
            }
            $invoice->forceFill(['customer_telegram_chat_id' => $chatId])->save();

            return $invoice;
        });
    }
}
