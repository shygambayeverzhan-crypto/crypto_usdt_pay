<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Merchant
 */
class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'initials' => $this->initials,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'back_url' => $this->back_url,
            'white_label_enabled' => $this->white_label_enabled,
            'invoice_expires_in_minutes' => $this->invoice_expires_in_minutes,
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

