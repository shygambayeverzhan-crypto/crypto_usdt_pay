<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\LoginHistory\LoginHistoryServiceContract;
use Illuminate\Auth\Events\Login;

class RecordUserLogin
{
    public function __construct(
        private readonly LoginHistoryServiceContract $loginHistoryService,
    ) {
    }

    public function handle(Login $event): void
    {
        $request = request();

        if ($request === null) {
            return;
        }

        if ($this->shouldSkip($request)) {
            return;
        }

        // защита от повторного срабатывания в рамках одного запроса
        if ($request->attributes->get('login_history_recorded') === true) {
            return;
        }

        $this->loginHistoryService->recordLogin($event->user, $request);
        $request->attributes->set('login_history_recorded', true);
    }

    private function shouldSkip($request): bool
    {
        // не пишем историю при impersonate start/leave
        if ($request->routeIs('impersonate.start') || $request->routeIs('impersonate.leave')) {
            return true;
        }

        if ($request->session()->get('impersonating', false)) {
            return true;
        }

        // логируем только классический логин/2fa завершение
        $path = $request->path();
        $isLoginRoute = $request->routeIs('login') || $path === 'login';
        $isTwoFactorChallenge = $request->routeIs('two-factor.login') || $request->routeIs('two-factor.challenge') || $path === 'two-factor-challenge';

        return ! ($isLoginRoute || $isTwoFactorChallenge);
    }
}

