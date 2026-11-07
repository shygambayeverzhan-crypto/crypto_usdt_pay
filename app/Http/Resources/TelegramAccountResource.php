<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Contracts\Telegram\TelegramServiceContract;
use App\Models\TelegramAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelegramAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $telegram = app(TelegramServiceContract::class);
        /** @var TelegramAccount $account */
        $account = $this->resource;

        return [
            'is_active' => (bool) $this->is_active,
            'chat_id' => $this->chat_id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'linked_at' => $this->linked_at?->toDateTimeString(),
            'link_token' => $this->link_token,
            'start_link' => $telegram->buildDeepLink($account),
            'bot_username' => $telegram->botUsername(),
        ];
    }
}

