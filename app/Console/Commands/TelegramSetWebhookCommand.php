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

        if (! is_array($botConfig)) {
            $this->error("Бот {$botName} не найден в config/telegram.php");

            return self::FAILURE;
        }

        $token = Arr::get($botConfig, 'token');
        $webhookUrl = Arr::get($botConfig, 'webhook_url');
        $allowedUpdates = Arr::get($botConfig, 'allowed_updates');
        $secretToken = Arr::get($botConfig, 'secret_token');

        if (! $token || ! $webhookUrl) {
            $this->error('Не заданы TELEGRAM_BOT_TOKEN или TELEGRAM_WEBHOOK_URL.');

            return self::FAILURE;
        }

        if (parse_url($webhookUrl, PHP_URL_SCHEME) !== 'https'
            || ! is_string($secretToken) || ! preg_match('/^[A-Za-z0-9_-]{32,256}$/', $secretToken)) {
            $this->error('Webhook requires HTTPS and TELEGRAM_WEBHOOK_SECRET (32–256 letters, digits, _ or -).');

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

        try {
            $bot = Telegram::bot($botName);
            $bot->setTimeOut(15);
            $me = $bot->getMe();
            $expectedName = ltrim((string) Arr::get($botConfig, 'username'), '@');
            if ($expectedName === '' || strcasecmp($expectedName, (string) $me->username) !== 0) {
                $this->error('TELEGRAM_BOT_NAME does not match the bot token. Webhook unchanged.');

                return self::FAILURE;
            }
            if (! $bot->setWebhook($payload)) {
                $this->error('Telegram did not accept the webhook.');

                return self::FAILURE;
            }
            $info = $bot->getWebhookInfo();
            if ($info->url !== $webhookUrl) {
                $this->error('Webhook verification failed.');

                return self::FAILURE;
            }
        } catch (\Throwable) {
            // SDK exception URLs may contain the bot token.
            $this->error('Telegram configuration failed. Check the private token and connectivity.');

            return self::FAILURE;
        }

        $this->info('Webhook verified for @'.$expectedName.'.');

        return self::SUCCESS;
    }
}
