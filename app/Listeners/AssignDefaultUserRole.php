<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class AssignDefaultUserRole
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (!$user instanceof User) {
            return;
        }

        if ($user->roles()->exists()) {
            return;
        }

        $role = Role::query()->firstOrCreate(
            ['name' => 'user'],
            [
                'display_name' => __('messages.users.roles.user'),
                'description' => __('messages.users.roles.user_description'),
            ]
        );

        $user->roles()->attach($role->id);
    }
}


