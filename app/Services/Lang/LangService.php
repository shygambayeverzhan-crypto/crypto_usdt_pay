<?php

declare(strict_types=1);

namespace App\Services\Lang;

use App\Contracts\Lang\LangServiceContract;
use LaravelLangSyncInertia\Services\LangService as BaseLangService;

class LangService extends BaseLangService implements LangServiceContract
{
    protected array $loaded = [];

    public function load(string $file): array
    {
        if (isset($this->loaded[$file])) {
            return $this->loaded[$file];
        }

        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');
        $basePath = $this->getBasePath();

        $translations = $this->readFile($basePath, $locale, $file);
        $fallbackTranslations = $fallbackLocale && $fallbackLocale !== $locale
            ? $this->readFile($basePath, $fallbackLocale, $file)
            : [];

        $this->loaded[$file] = array_replace_recursive($fallbackTranslations, $translations);

        return $this->loaded[$file];
    }

    public function getFile(string|array $files): array
    {
        $files = (array) $files;

        return collect($files)->mapWithKeys(function ($file) {
            return [$file => $this->load($file)];
        })->all();
    }

    public function getLoaded(): array
    {
        return $this->loaded;
    }

    public function availableFiles(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $path = $this->getBasePath()."/{$locale}";

        if (! is_dir($path)) {
            return [];
        }

        $files = scandir($path) ?: [];

        return array_values(array_filter(array_map(function ($file) {
            return str_ends_with($file, '.php')
                ? pathinfo($file, PATHINFO_FILENAME)
                : null;
        }, $files)));
    }

    private function getBasePath(): string
    {
        return rtrim(config('inertia-lang.lang_path', lang_path()), '/');
    }

    private function readFile(string $basePath, string $locale, string $file): array
    {
        $path = "{$basePath}/{$locale}/{$file}.php";

        if (! is_file($path)) {
            return [];
        }

        $translations = require $path;

        return is_array($translations) ? $translations : [];
    }
}

