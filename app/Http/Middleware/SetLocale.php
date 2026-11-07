<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Lang\LanguageSettingsServiceContract;
use App\Support\LocaleOptions;
use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        /** @var LanguageSettingsServiceContract $languageSettingsService */
        $languageSettingsService = app(LanguageSettingsServiceContract::class);

        $allowed = $languageSettingsService->enabledLocaleCodes();

        $localeFromSession = $request->session()->get('locale');

        $candidate = $localeFromSession;
        if (! $request->session()->has('locale')) {
            $candidate = $request->getPreferredLanguage($allowed) ?? config('app.locale');
        }

        $candidate = str_replace('_', '-', (string) $candidate);
        $normalizedLocale = LocaleOptions::normalize($candidate) ?? config('app.locale');

        $locale = in_array($normalizedLocale, $allowed, true) ? $normalizedLocale : config('app.locale');

        app()->setLocale($locale);
        if ($localeFromSession !== $locale) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}

