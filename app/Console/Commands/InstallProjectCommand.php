<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstallProjectCommand extends Command
{
    protected $signature = 'app:install';

    protected $description = 'Создать администратора из ваших ADMIN_* настроек без сброса базы данных.';

    public function handle(): int
    {
        $credentials = [
            'name' => trim((string) config('setup.admin.name')),
            'email' => trim((string) config('setup.admin.email')),
            'password' => (string) config('setup.admin.password'),
        ];
        $validator = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            $this->error('Заполните ADMIN_NAME, ADMIN_EMAIL и ADMIN_PASSWORD (не менее 12 символов) в приватном .env.');
            return self::FAILURE;
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (User::query()->where('email', $credentials['email'])->exists()) {
            $this->error('Пользователь с указанным email уже существует. Его пароль и права не изменены.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($credentials): void {
            $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], [
                'display_name' => __('messages.users.roles.admin'),
                'description' => __('messages.users.roles.admin_description'),
            ]);
            Role::query()->firstOrCreate(['name' => 'user'], [
                'display_name' => __('messages.users.roles.user'),
                'description' => __('messages.users.roles.user_description'),
            ]);

            $user = User::query()->create($credentials);
            $user->forceFill(['approved_at' => now(), 'email_verified_at' => now()])->save();
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        });

        $this->info('Администратор создан. Удалите ADMIN_PASSWORD из .env и обновите кеш конфигурации. API-ключи доступны в кабинете.');
        return self::SUCCESS;
    }
}
