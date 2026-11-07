<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Enums\Currency;
use App\Contracts\Lang\LanguageSettingsServiceContract;
use App\Http\Requests\UpdateAppSettingsRequest;
use App\Http\Requests\UpdateLanguageSettingsRequest;
use App\Http\Resources\AppSettingResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index(
        AppSettingsServiceContract $service,
        LanguageSettingsServiceContract $languageSettingsService
    ): Response {
        $settings = $service->all();
        return $this->inertia('app-settings/Index', [
            'settings' => AppSettingResource::collection($settings)->resolve(),
            'currencies' => array_map(fn (Currency $c) => $c->value, Currency::cases()),
            'locales' => $languageSettingsService->availableLocales(),
            'enabled_locales' => $languageSettingsService->enabledLocaleCodes(),
        ]);
    }

    public function update(UpdateAppSettingsRequest $request, AppSettingsServiceContract $service): RedirectResponse
    {
        foreach ($request->toMinorMap(app('App\\Contracts\\Money\\MoneyServiceContract')) as $item) {
            $service->upsert($item['currency'], $item['min_minor'], $item['max_minor']);
        }

        return back();
    }

    public function updateLocales(
        UpdateLanguageSettingsRequest $request,
        LanguageSettingsServiceContract $languageSettingsService
    ): RedirectResponse {
        $languageSettingsService->updateEnabledLocales($request->validated()['locales'] ?? null);

        return back();
    }
}


