<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()->all(), []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'can_manage_role' => $this->id !== Auth::id(),
            'can_be_impersonated' => Auth::user()?->canImpersonate() && $this->canBeImpersonated() && $this->id !== Auth::id(),
        ];
    }
}


