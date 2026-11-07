<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function inertia(string $component, array $props = [], array|string $langFiles = '*'): Response
    {
        \syncLangFiles($this->resolveLangFiles($langFiles));

        return Inertia::render($component, $props);
    }

    private function resolveLangFiles(array|string $langFiles): array
    {
        if ($langFiles !== '*') {
            return (array) $langFiles;
        }

        $locale = app()->getLocale();
        $basePath = rtrim(config('inertia-lang.lang_path', resource_path('lang')), '/');
        $path = "{$basePath}/{$locale}";

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
}
