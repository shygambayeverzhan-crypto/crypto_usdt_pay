<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Contracts\Telegram\TelegramServiceContract;
use App\Services\Telegram\Exceptions\TelegramServiceException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

final class StartCommand extends Command
{
    protected string $name = 'start';

    protected string $description = 'Link account with Telegram bot';

    public function __construct(
        private readonly TelegramServiceContract $telegramService,
    ) {}

    public function handle(): void
    {
        $token = trim(explode(' ', $this->getUpdate()->getMessage()->text ?? '')[1] ?? '');
        $message = $this->getUpdate()->getMessage();
        $chat = $message?->getChat();
        $from = $message?->getFrom();

        if (! $chat || $chat->type !== 'private') {
            return;
        }
        if ($token === '') {
            $this->replyWithMessage([
                'text' => "Бот приёма USDT в сети TRON.\nОткройте персональную ссылку заказа от продавца, чтобы получать статус оплаты.\n/status — ваши последние платежи.\nПодтверждение появится после проверки транзакции и подтверждений сети.",
            ]);

            return;
        }

        if (str_starts_with($token, 'pay_')) {
            $invoice = app(\App\Services\Telegram\CustomerPaymentLink::class)->bind(substr($token, 4), (string) $chat->id);
            $this->replyWithMessage(['text' => $invoice
                ? 'Заказ: '.$invoice->id."\nСтатус: ".$invoice->status->value."\n".route('invoices.public', $invoice)
                : 'Ссылка недействительна или уже привязана к другому чату.']);

            return;
        }

        try {
            $this->telegramService->handleStart($token, $from?->toArray() ?? [], (string) $chat->id);

            $this->replyWithMessage([
                'text' => __('messages.notifications.telegram.start_success'),
            ]);
        } catch (TelegramServiceException $exception) {
            $this->replyWithMessage([
                'text' => $exception->getMessage(),
            ]);
        } catch (\Throwable $throwable) {
            Log::warning('Telegram start command failed', [
                'error' => $throwable->getMessage(),
            ]);

            $this->replyWithMessage([
                'text' => __('messages.notifications.telegram.start_error'),
            ]);
        }
    }
}
