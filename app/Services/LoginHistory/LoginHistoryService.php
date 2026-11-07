<?php

declare(strict_types=1);

namespace App\Services\LoginHistory;

use App\Contracts\IpGeolocation\IpGeolocationServiceContract;
use App\Contracts\LoginHistory\LoginHistoryServiceContract;
use App\Models\User;
use App\Models\UserLoginHistory;
use App\Services\IpGeolocation\IpGeolocationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class LoginHistoryService implements LoginHistoryServiceContract
{
    public function __construct(
        private readonly IpGeolocationServiceContract $ipGeolocationService,
    ) {
    }

    public function recordLogin(User $user, ?Request $request = null): UserLoginHistory
    {
        $ip = $request?->ip();
        $userAgent = $request?->userAgent();

        $geoResult = $this->ipGeolocationService->lookup((string) $ip);

        return UserLoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $this->detectDeviceType($userAgent),
            'platform' => $this->detectPlatform($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'geo_status' => $geoResult->status,
            'geo' => $geoResult->payload,
        ]);
    }

    private function detectDeviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $ua = Str::lower($userAgent);

        if (Str::contains($ua, ['ipad', 'tablet'])) {
            return 'tablet';
        }

        if (Str::contains($ua, ['iphone', 'android', 'mobile'])) {
            return 'mobile';
        }

        if (Str::contains($ua, ['bot', 'crawl', 'spider'])) {
            return 'bot';
        }

        return 'desktop';
    }

    private function detectPlatform(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $ua = Str::lower($userAgent);

        if (Str::contains($ua, 'windows')) {
            return 'Windows';
        }
        if (Str::contains($ua, ['macintosh', 'mac os'])) {
            return 'macOS';
        }
        if (Str::contains($ua, 'linux')) {
            return 'Linux';
        }
        if (Str::contains($ua, 'android')) {
            return 'Android';
        }
        if (Str::contains($ua, ['iphone', 'ipad'])) {
            return 'iOS';
        }

        return null;
    }

    private function detectBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $ua = Str::lower($userAgent);

        return match (true) {
            Str::contains($ua, 'edg') => 'Edge',
            Str::contains($ua, 'chrome') => 'Chrome',
            Str::contains($ua, 'safari') && ! Str::contains($ua, 'chrome') => 'Safari',
            Str::contains($ua, 'firefox') => 'Firefox',
            Str::contains($ua, 'opera') || Str::contains($ua, 'opr/') => 'Opera',
            default => null,
        };
    }
}

