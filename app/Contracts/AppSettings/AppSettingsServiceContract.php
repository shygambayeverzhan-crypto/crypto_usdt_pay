<?php

declare(strict_types=1);

namespace App\Contracts\AppSettings;

use App\Enums\Currency;
use App\Models\AppSetting;
use Illuminate\Support\Collection;

interface AppSettingsServiceContract
{
    /** Получить все настройки по валютам */
    public function all(): Collection; // of AppSetting

    /** Получить настройки для валюты */
    public function get(Currency $currency): ?AppSetting;

    /** Обновить или создать настройки для валюты */
    public function upsert(Currency $currency, string $minMinor, string $maxMinor): AppSetting;
}


