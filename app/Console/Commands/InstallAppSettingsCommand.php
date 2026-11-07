<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use Illuminate\Console\Command;

class InstallAppSettingsCommand extends Command
{
    protected $signature = 'app:install-settings';

    protected $description = 'Установить дефолтные глобальные настройки приложения (минимум/максимум суммы инвойсов по валютам).';

    public function handle(AppSettingsServiceContract $settings, MoneyServiceContract $money): int
    {
        foreach (Currency::cases() as $currency) {
            // Дефолты: 0.000001 .. 1_000_000 по каждой валюте
            $minMinor = $money->toMinor($money->create('10', $currency));
            $maxMinor = $money->toMinor($money->create('1000000', $currency));
            $settings->upsert($currency, $minMinor, $maxMinor);
        }

        $this->info('App settings installed/updated.');
        return self::SUCCESS;
    }
}


