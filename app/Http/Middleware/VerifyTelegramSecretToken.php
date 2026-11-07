<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramSecretToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $botName = config('telegram.default', 'mybot');
        $expectedToken = config("telegram.bots.{$botName}.secret_token");
        $providedToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!is_string($expectedToken) || $expectedToken === '' || !is_string($providedToken) || !hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

