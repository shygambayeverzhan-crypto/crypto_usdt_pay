<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RailwayPrepareCommand extends Command
{
    protected $signature = 'railway:prepare';

    protected $description = 'Wait for PostgreSQL and initialize a managed container without resetting data';

    public function handle(): int
    {
        if (! config('app.key') || config('database.default') !== 'pgsql') {
            $this->error('Configure a persistent APP_KEY and PostgreSQL before starting.');

            return self::FAILURE;
        }
        $ready = false;
        for ($attempt = 0; $attempt < 30; $attempt++) {
            try {
                DB::select('select 1');
                $ready = true;
                break;
            } catch (\Throwable) {
                // Never log connection URLs or passwords while waiting for the database.
                DB::purge();
                sleep(2);
            }
        }
        if (! $ready) {
            $this->error('PostgreSQL is unavailable; application startup stopped.');

            return self::FAILURE;
        }
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        // Bootstrap only a completely empty installation. Never reset existing credentials.
        if (! User::query()->exists() && config('setup.admin.email') && config('setup.admin.password')) {
            if ($this->call('app:install') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        if (! AppSetting::query()->exists()) {
            if ($this->call('app:install-settings') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        if (config('railway.auto_webhook') && $this->call('telegram:set-webhook') !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
