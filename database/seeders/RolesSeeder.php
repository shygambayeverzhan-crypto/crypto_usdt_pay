<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Администратор', 'description' => 'Администратор'],
            ['name' => 'user', 'display_name' => 'Пользователь', 'description' => 'Обычный пользователь'],
        ];

        $roleIds = [];
        foreach ($roles as $roleData) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                ]
            );
            $roleIds[$roleData['name']] = $role->id;
        }

        $adminUser = User::query()->orderBy('id')->first();
        if ($adminUser && isset($roleIds['admin'])) {
            $attachable = array_filter([
                $roleIds['admin'],
                $roleIds['user'] ?? null,
            ]);
            $adminUser->roles()->syncWithoutDetaching($attachable);
        }
    }
}


