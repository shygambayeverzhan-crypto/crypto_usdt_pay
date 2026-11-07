<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\Telegram\TelegramServiceContract;
use App\Models\Notification;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Services\Telegram\Exceptions\TelegramAccountNotLinkedException;
use App\Services\Telegram\Exceptions\TelegramServiceException;
use App\Services\Telegram\Exceptions\TelegramStartTokenInvalidException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

final class TelegramService implements TelegramServiceContract
{
    public function getOrCreateForUser(User $user): TelegramAccount
    {
        $account = $user->telegramAccount()->firstOrNew([
            'user_id' => $user->id,
        ]);

        if (!$account->link_token) {
            $account->link_token = $this->generateToken();
        }

        if (!$account->exists || $account->isDirty()) {
            $account->save();
        }

        return $account;
    }

    public function refreshLink(User $user): TelegramAccount
    {
        $account = $this->getOrCreateForUser($user);

        $account->fill([
            'chat_id' => null,
            'username' => null,
            'first_name' => null,
            'last_name' => null,
            'linked_at' => null,
            'is_active' => false,
            'link_token' => $this->generateToken(),
        ]);

        $account->save();

        return $account;
    }

    public function handleStart(string $token, array $telegramUser, string $chatId): TelegramAccount
    {
        $account = TelegramAccount::query()
            ->where('link_token', $token)
            ->first();

        if (!$account) {
            throw new TelegramStartTokenInvalidException(__('messages.notifications.telegram.invalid_token'));
        }

        $account->fill([
            'chat_id' => $chatId,
            'username' => Arr::get($telegramUser, 'username'),
            'first_name' => Arr::get($telegramUser, 'first_name'),
            'last_name' => Arr::get($telegramUser, 'last_name'),
            'linked_at' => now(),
            'is_active' => true,
        ]);

        $account->save();

        return $account;
    }

    public function sendNotification(Notification $notification): void
    {
        $user = $notification->user()->first();

        if (!$user) {
            throw new TelegramServiceException('Пользователь уведомления не найден.');
        }

        $account = $user->telegramAccount()
            ->where('is_active', true)
            ->first();

        if (!$account || !$account->chat_id) {
            throw new TelegramAccountNotLinkedException(__('messages.notifications.telegram.not_linked'));
        }

        $text = trim($notification->title."\n\n".$notification->body);

        try {
            Telegram::sendMessage([
                'chat_id' => $account->chat_id,
                'text' => $text,
            ]);
        } catch (TelegramSDKException $exception) {
            throw new TelegramServiceException(
                __('messages.notifications.telegram.delivery_failed'),
                previous: $exception
            );
        }
    }

    public function botUsername(): ?string
    {
        $defaultBot = config('telegram.default', 'mybot');
        $botConfig = config("telegram.bots.$defaultBot", []);
        $username = $botConfig['username'] ?? null;

        if (is_string($username) && $username !== '') {
            return ltrim($username, '@');
        }

        return null;
    }

    public function buildDeepLink(TelegramAccount $account): ?string
    {
        $username = $this->botUsername();

        if (!$username) {
            return null;
        }

        return sprintf('https://t.me/%s?start=%s', $username, $account->link_token);
    }

    private function generateToken(): string
    {
        return Str::random(32);
    }
}

