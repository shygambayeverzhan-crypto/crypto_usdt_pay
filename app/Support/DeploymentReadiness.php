<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

final class DeploymentReadiness
{
    /** Codes only: never include connection strings or exception messages. */
    public function configurationErrors(): array
    {
        $errors = [];
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        if (! Encrypter::supported($key, (string) config('app.cipher'))) {
            $errors[] = 'APP_KEY_INVALID';
        }
        if (config('vercel.enabled')) {
            $database = config('database.connections.pgsql', []);
            if (config('database.default') !== 'pgsql') {
                $errors[] = 'POSTGRES_REQUIRED';
            }
            if (! filled($database['url'] ?? null) && ! collect(['host', 'database', 'username', 'password'])
                ->every(fn ($name) => filled($database[$name] ?? null))) {
                $errors[] = 'DATABASE_NOT_CONFIGURED';
            }
        }

        return $errors;
    }

    public function errors(): array
    {
        if ($errors = $this->configurationErrors()) {
            return $errors;
        }
        try {
            DB::connection()->select('select 1');
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                return ['MIGRATIONS_REQUIRED'];
            }
            $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
            if (array_diff(array_keys($files), $migrator->getRepository()->getRan())) {
                return ['MIGRATIONS_REQUIRED'];
            }
        } catch (\Throwable) {
            return ['DATABASE_UNAVAILABLE'];
        }

        return [];
    }
}
