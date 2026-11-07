<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserLoginHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoginHistoryResource extends JsonResource
{
    /** @var UserLoginHistory */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'device_type' => $this->device_type,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'geo_status' => $this->geo_status,
            'geo' => $this->geo,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

