<?php

declare(strict_types=1);

namespace App\Contracts\Lang;

use App\Models\LanguageSetting;

interface LanguageSettingsServiceContract
{
    public function availableLocales(): array;

    public function enabledLocaleCodes(): array;

    public function updateEnabledLocales(?array $codes): LanguageSetting;
}


