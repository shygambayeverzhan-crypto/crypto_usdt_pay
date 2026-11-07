<?php

declare(strict_types=1);

namespace App\Services\Lang;

use App\Contracts\Lang\LanguageSettingsServiceContract;
use App\Models\LanguageSetting;
use App\Support\LocaleOptions;

final class LanguageSettingsService implements LanguageSettingsServiceContract
{
    public function availableLocales(): array
    {
        return LocaleOptions::all();
    }

    public function enabledLocaleCodes(): array
    {
        $setting = $this->getSetting();
        $saved = (array) ($setting?->enabled_locales ?? []);

        if (empty($saved)) {
            return LocaleOptions::codes();
        }

        $allowed = array_map('strval', $saved);

        return array_values(array_intersect(LocaleOptions::codes(), $allowed));
    }

    public function updateEnabledLocales(?array $codes): LanguageSetting
    {
        $normalized = array_filter(array_map(function ($code) {
            return LocaleOptions::normalize((string) $code);
        }, $codes ?? []));

        $normalized = array_values(array_unique($normalized));

        if (count($normalized) === count(LocaleOptions::codes())) {
            $normalized = [];
        }

        $setting = $this->getSetting() ?? new LanguageSetting();
        $setting->enabled_locales = empty($normalized) ? null : array_values($normalized);
        $setting->save();

        return $setting;
    }

    private function getSetting(): ?LanguageSetting
    {
        return LanguageSetting::query()->first();
    }
}


