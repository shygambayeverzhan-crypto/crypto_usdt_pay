<?php

namespace App\Telegram\Commands;

use App\Models\Invoice;
use Telegram\Bot\Commands\Command;

final class StatusCommand extends Command
{
    protected string $name = 'status';

    protected string $description = 'Статусы ваших платежей';

    public function handle(): void
    {
        $chat = $this->getUpdate()->getMessage()?->getChat();
        if (! $chat || $chat->type !== 'private') {
            return;
        }
        $invoices = Invoice::query()->where('customer_telegram_chat_id', (string) $chat->id)->latest()->limit(5)->get();
        $text = $invoices->map(fn ($i) => $i->id.': '.$i->status->value.' ('.$i->confirmations.' подтверждений)')->implode("\n");
        $this->replyWithMessage(['text' => $text ?: 'Откройте персональную ссылку бота, полученную от продавца.']);
    }
}
