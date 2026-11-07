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
    ) {
    }

    public function handle(): void
    {
        $token = trim(explode(' ', $this->getUpdate()->getMessage()->text ?? '')[1] ?? '');
        $message = $this->getUpdate()->getMessage();
        $chat = $message?->getChat();
        $from = $message?->getFrom();

        if (!$chat || $token === '') {
            $this->replyWithMessage([
                'text' => __('messages.notifications.telegram.start_missing_token'),
            ]);

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

