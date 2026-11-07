<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallProjectCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     */
    protected $description = 'Полная установка проекта: migrate:fresh без подтверждений и создание пользователя admin';

    public function handle(): int
    {
        // 1) Полный сброс и повторный запуск всех миграций (без подтверждений)
        $this->info('Выполняю migrate:fresh...');
        $this->call('migrate:fresh', ['--force' => true]);

        // 2) Создание единственного пользователя admin
        $this->info('Создаю пользователя admin...');
        $user = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'password' => 'admin', // будет захеширован через cast в модели
            ],
        );

        // 3) Роли admin и user + назначение админу
        $this->info('Создаю роли admin/user и назначаю админу...');
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => __('messages.users.roles.admin'),
                'description' => __('messages.users.roles.admin_description'),
            ]
        );
        Role::query()->firstOrCreate(
            ['name' => 'user'],
            [
                'display_name' => __('messages.users.roles.user'),
                'description' => __('messages.users.roles.user_description'),
            ]
        );
        $user->roles()->sync([$adminRole->id]);

        // 4) API токен для администратора (если отсутствует)
        $this->info('Проверяю API токен администратора...');
        if (!$user->apiTokens()->exists()) {
            $user->apiTokens()->create([
                'name' => 'Default',
                'token' => $this->generateUniqueToken(),
            ]);
            $this->line('API токен создан для admin@admin.com');
        } else {
            $this->line('API токен уже существует, пропускаю.');
        }

        $this->line('Создан пользователь:');
        $this->line('  name: admin');
        $this->line('  email: admin@admin.com');
        $this->line('  password: admin');

        $this->info('Установка завершена.');
        return self::SUCCESS;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (ApiToken::query()->where('token', $token)->exists());

        return $token;
    }
}


