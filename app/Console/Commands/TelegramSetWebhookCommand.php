<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {bot? : Имя бота из config/telegram.php (по умолчанию — default)}';

    protected $description = 'Установить webhook для Telegram-бота из конфигурации';

    public function handle(): int
    {
        $defaultBot = config('telegram.default', 'mybot');
        $botName = (string) ($this->argument('bot') ?? $defaultBot);
        $botConfig = config("telegram.bots.$botName");

        if (!is_array($botConfig)) {
            $this->error("Бот {$botName} не найден в config/telegram.php");
            return self::FAILURE;
        }

        $token = Arr::get($botConfig, 'token');
        $webhookUrl = Arr::get($botConfig, 'webhook_url');
        $allowedUpdates = Arr::get($botConfig, 'allowed_updates');
        $secretToken = Arr::get($botConfig, 'secret_token');

        if (!$token || !$webhookUrl) {
            $this->error('Не заданы TELEGRAM_BOT_TOKEN или TELEGRAM_WEBHOOK_URL.');
            return self::FAILURE;
        }

        $this->info("Устанавливаем webhook для бота '{$botName}' на {$webhookUrl}");

        $payload = [
            'url' => $webhookUrl,
        ];

        if (is_string($secretToken) && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        if (is_array($allowedUpdates) || $allowedUpdates === null) {
            $payload['allowed_updates'] = $allowedUpdates;
        }

        $response = Telegram::bot($botName)->setWebhook($payload);

        $this->info('Ответ Telegram: '.json_encode($response, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}

