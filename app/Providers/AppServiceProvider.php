<?php

namespace App\Providers;

use App\Contracts\Address\AddressServiceContract;
use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Blockchain\ExplorerServiceContract;
use App\Contracts\Client\ClientServiceContract;
use App\Contracts\Invoice\InvoiceCallbackServiceContract;
use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\IpGeolocation\IpGeolocationServiceContract;
use App\Contracts\Lang\LangServiceContract;
use App\Contracts\Lang\LanguageSettingsServiceContract;
use App\Contracts\LoginHistory\LoginHistoryServiceContract;
use App\Contracts\Merchant\MerchantServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Contracts\Notification\NotificationServiceContract;
use App\Contracts\Store\StoreServiceContract;
use App\Contracts\Telegram\TelegramServiceContract;
use App\Contracts\WalletTransfer\WalletTransferServiceContract;
use App\Mixins\ResponseMixins;
use App\Services\Address\AddressService;
use App\Services\AppSettings\AppSettingsService;
use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\Explorer\ExplorerService;
use App\Services\Client\ClientService;
use App\Services\Invoice\InvoiceCallbackService;
use App\Services\Invoice\InvoiceService;
use App\Services\IpGeolocation\IpGeolocationService;
use App\Services\Lang\LangService;
use App\Services\Lang\LanguageSettingsService;
use App\Services\LoginHistory\LoginHistoryService;
use App\Services\Merchant\MerchantService;
use App\Services\Money\MoneyService;
use App\Services\Notification\NotificationService;
use App\Services\Store\StoreService;
use App\Services\Telegram\TelegramService;
use App\Services\WalletTransfer\WalletTransferService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use LaravelLangSyncInertia\Services\LangService as VendorLangService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AddressServiceContract::class, AddressService::class);
        $this->app->bind(InvoiceServiceContract::class, InvoiceService::class);
        $this->app->bind(InvoiceCallbackServiceContract::class, InvoiceCallbackService::class);
        $this->app->singleton(MoneyServiceContract::class, MoneyService::class);
        $this->app->singleton(BlockchainServiceContract::class, BlockchainService::class);
        $this->app->singleton(ExplorerServiceContract::class, ExplorerService::class);
        $this->app->singleton(AppSettingsServiceContract::class, AppSettingsService::class);
        $this->app->singleton(IpGeolocationServiceContract::class, IpGeolocationService::class);
        $this->app->singleton(LangServiceContract::class, LangService::class);
        $this->app->singleton(LanguageSettingsServiceContract::class, LanguageSettingsService::class);
        $this->app->singleton(LoginHistoryServiceContract::class, LoginHistoryService::class);
        $this->app->singleton(MerchantServiceContract::class, MerchantService::class);
        $this->app->singleton(ClientServiceContract::class, ClientService::class);
        $this->app->singleton(StoreServiceContract::class, StoreService::class);
        $this->app->singleton(VendorLangService::class, LangService::class);
        $this->app->singleton(NotificationServiceContract::class, NotificationService::class);
        $this->app->singleton(TelegramServiceContract::class, TelegramService::class);
        $this->app->singleton(WalletTransferServiceContract::class, WalletTransferService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        Response::mixin(new ResponseMixins);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api.v1', function (Request $request) {
            $ipKey = 'ip:'.(string) $request->ip();
            $apiKey = (string) $request->header('X-Api-Key', '');
            $keyIdentifier = $apiKey !== '' ? 'key:'.hash('sha256', $apiKey) : 'key:missing|'.$ipKey;

            return collect([
                Limit::perMinute(60)->by($ipKey),
                Limit::perMinute(120)->by($keyIdentifier),
            ])->map(function (Limit $limit) {
                return $limit->response(function () {
                    return response()->json([
                        'message' => __('messages.api.rate_limited'),
                    ], 429);
                });
            })->all();
        });
    }
}
