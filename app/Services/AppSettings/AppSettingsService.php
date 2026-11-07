<?php

declare(strict_types=1);

namespace App\Services\AppSettings;

use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Enums\Currency;
use App\Models\AppSetting;
use Illuminate\Support\Collection;

final class AppSettingsService implements AppSettingsServiceContract
{
    public function all(): Collection
    {
        return AppSetting::query()->orderBy('currency')->get();
    }

    public function get(Currency $currency): ?AppSetting
    {
        return AppSetting::query()->where('currency', $currency->value)->first();
    }

    public function upsert(Currency $currency, string $minMinor, string $maxMinor): AppSetting
    {
        return tap(
            AppSetting::query()->firstOrNew(['currency' => $currency->value]),
            function (AppSetting $model) use ($minMinor, $maxMinor): void {
                $model->min_invoice_amount_minor = $minMinor;
                $model->max_invoice_amount_minor = $maxMinor;
                $model->save();
            }
        );
    }
}


