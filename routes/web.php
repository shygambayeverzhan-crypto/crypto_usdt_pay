<?php

use App\Contracts\Lang\LanguageSettingsServiceContract;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiTokenAllowedIpController;
use App\Http\Controllers\CallbackLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\CallbackSandboxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationRuleController;
use App\Http\Controllers\TelegramSettingsController;
use App\Http\Controllers\TelegramWebhookController;
use App\Support\LocaleOptions;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('telegram/webhook', TelegramWebhookController::class)->middleware('telegram.secret')->name('telegram.webhook');

Route::get('lang/{locale}', function (string $locale, LanguageSettingsServiceContract $languageSettingsService) {
    $normalizedLocale = LocaleOptions::normalize($locale);

    if (! $normalizedLocale) {
        abort(404);
    }

    $allowedLocales = $languageSettingsService->enabledLocaleCodes();

    if (! in_array($normalizedLocale, $allowedLocales, true)) {
        abort(404);
    }

    session(['locale' => $normalizedLocale]);
    app()->setLocale($normalizedLocale);

    return back();
})->name('locale.switch');

// Public payment page
Route::get('pay/{invoice}', [InvoiceController::class, 'public'])->name('invoices.public');
Route::get('pay/{invoice}/data', [InvoiceController::class, 'publicData'])->name('invoices.public.data');
Route::get('pay/{invoice}/qr', [InvoiceController::class, 'publicQr'])->name('invoices.public.qr');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');

    // Clients
    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::patch('clients/{client}', [ClientController::class, 'update'])->name('clients.update');

    // Merchants
    Route::get('merchants', [MerchantController::class, 'index'])->name('merchants.index');
    Route::post('merchants', [MerchantController::class, 'store'])->name('merchants.store');
    Route::patch('merchants/{merchant}', [MerchantController::class, 'update'])->name('merchants.update');

    // Invoices
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('invoices/{invoice}/send-callback', [InvoiceController::class, 'sendCallback'])->name('invoices.send-callback');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::patch('notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.mark-unread');

    Route::post('notifications/rules', [NotificationRuleController::class, 'store'])->name('notifications.rules.store');
    Route::patch('notifications/rules/{notificationRule}', [NotificationRuleController::class, 'update'])->name('notifications.rules.update');
    Route::delete('notifications/rules/{notificationRule}', [NotificationRuleController::class, 'destroy'])->name('notifications.rules.destroy');
    Route::post('notifications/telegram/link', [TelegramSettingsController::class, 'refreshLink'])->name('notifications.telegram.refresh');

    // Callback Logs
    Route::get('callback-logs', [CallbackLogController::class, 'index'])->name('callback-logs.index');

    // App Settings (глобальные настройки проекта) — только admin
    Route::middleware('role:admin')->group(function () {
        Route::get('app-settings', [\App\Http\Controllers\AppSettingsController::class, 'index'])->name('app-settings.index');
        Route::put('app-settings', [\App\Http\Controllers\AppSettingsController::class, 'update'])->name('app-settings.update');
        Route::put('app-settings/locales', [\App\Http\Controllers\AppSettingsController::class, 'updateLocales'])->name('app-settings.locales.update');
    });

    // API Docs & Playground (не в настройках)
    Route::get('api', ApiController::class)->name('api.docs');
    Route::post('api/regenerate-token', [ApiController::class, 'regenerate'])->name('api.regenerate-token');
    Route::post('api/regenerate-callback-token', [ApiController::class, 'regenerateCallback'])->name('api.regenerate-callback-token');
    Route::post('api/allowed-ips', [ApiTokenAllowedIpController::class, 'store'])->name('api.allowed-ips.store');
    Route::delete('api/allowed-ips/{allowedIp}', [ApiTokenAllowedIpController::class, 'destroy'])->name('api.allowed-ips.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('users', [UsersController::class, 'index'])->name('users.index');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/approval', [UsersController::class, 'updateApproval'])->name('users.approval.update');
        Route::post('impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });

Route::post('impersonate/leave', [ImpersonationController::class, 'leave'])
    ->middleware('auth')
    ->name('impersonate.leave');

require __DIR__.'/settings.php';

if (app()->environment(['local', 'development', 'dev'])) {
    Route::post('/dev/callback-sandbox', CallbackSandboxController::class)->name('dev.callback.sandbox');
}
