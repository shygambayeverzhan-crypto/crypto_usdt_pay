<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWebhookController extends Controller
{
    public function __invoke(): Response
    {
        try {
            Telegram::commandsHandler(true);
        } catch (\Throwable $throwable) {
            Log::warning('Telegram webhook handler failed', [
                'exception_class' => $throwable::class,
            ]);

            // Telegram retries failed deliveries; a 204 would silently lose the command.
            return response('Temporarily unavailable', 503);
        }

        return response()->noContent();
    }
}
