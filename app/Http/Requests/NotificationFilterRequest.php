<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $event Тип события
 * @property-read string|null $channel Канал уведомления
 * @property-read string|null $delivery_status Статус доставки
 * @property-read bool|null $only_unread Только непрочитанные
 */
class NotificationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string', 'max:64'],
            'channel' => ['nullable', 'string', 'max:32'],
            'delivery_status' => ['nullable', 'string', 'max:32'],
            'only_unread' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return [
            'event' => $this->input('event'),
            'channel' => $this->input('channel'),
            'delivery_status' => $this->input('delivery_status'),
            'only_unread' => (bool) $this->boolean('only_unread'),
        ];
    }
}

