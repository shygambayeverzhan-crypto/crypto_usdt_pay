<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Telegram\TelegramServiceContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TelegramSettingsController extends Controller
{
    public function refreshLink(TelegramServiceContract $telegram): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $telegram->refreshLink($user);
        }

        return back()->with('success', __('messages.notifications.telegram.link_refreshed'));
    }
}

