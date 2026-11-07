<?php

declare(strict_types=1);

namespace App\Support;

final class LocaleOptions
{
    private const AVAILABLE = [
        ['code' => 'ru', 'label' => 'Русский', 'flag' => 'RU'],
        ['code' => 'en', 'label' => 'English', 'flag' => 'US'],
    ];

    public static function all(): array
    {
        return self::AVAILABLE;
    }

    public static function codes(): array
    {
        return array_column(self::AVAILABLE, 'code');
    }

    public static function find(string $code): ?array
    {
        foreach (self::AVAILABLE as $locale) {
            if ($locale['code'] === $code) {
                return $locale;
            }
        }

        return null;
    }

    public static function normalize(string $code): ?string
    {
        foreach (self::AVAILABLE as $locale) {
            if (strcasecmp($locale['code'], $code) === 0) {
                return $locale['code'];
            }
        }

        return null;
    }
}


