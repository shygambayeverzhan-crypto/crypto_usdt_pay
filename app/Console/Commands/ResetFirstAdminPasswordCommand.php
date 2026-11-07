<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetFirstAdminPasswordCommand extends Command
{
    protected $signature = 'user:reset-first-admin-password {password}';

    protected $description = 'Сбросить пароль для пользователя с ID 1.';

    public function handle(): int
    {
        $user = User::query()->find(1);

        if (!$user) {
            $this->error('Пользователь с ID 1 не найден.');

            return self::FAILURE;
        }

        $password = (string) $this->argument('password');

        if ($password === '') {
            $this->error('Пароль не может быть пустым.');

            return self::FAILURE;
        }

        $user->password = $password;
        $user->save();

        $this->info('Пароль пользователя с ID 1 успешно обновлен.');

        return self::SUCCESS;
    }
}
