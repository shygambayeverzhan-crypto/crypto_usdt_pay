<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'channel' => $this->channel?->value,
            'status' => $this->status?->value,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload,
            'read_at' => $this->read_at?->toDateTimeString(),
            'delivered_at' => $this->delivered_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

