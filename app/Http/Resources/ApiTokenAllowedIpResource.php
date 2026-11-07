<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiTokenAllowedIpResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}


