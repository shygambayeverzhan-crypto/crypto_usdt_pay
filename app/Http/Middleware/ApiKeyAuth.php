<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $provided = (string) $request->header('X-Api-Key', '');
        if ($provided === '') {
            return $this->unauthorized();
        }

        $token = ApiToken::query()
            ->with(['user', 'allowedIps'])
            ->where('token', $provided)
            ->first();

        if (!$token || !$token->user) {
            return $this->unauthorized();
        }

        $requestIp = (string) $request->ip();
        $allowedIps = $token->allowedIps;

        if ($allowedIps->isNotEmpty()) {
            $normalizedRequestIp = $this->normalizeIp($requestIp);
            $isAllowed = $allowedIps->contains(function ($allowed) use ($normalizedRequestIp) {
                return $this->normalizeIp($allowed->ip) === $normalizedRequestIp;
            });

            if (!$isAllowed) {
                return response()->json([
                    'message' => __('messages.api.ip_not_allowed'),
                ], 403);
            }
        }

        $token->updateQuietly(['last_used_at' => Carbon::now()]);

        Auth::setUser($token->user);

        return $next($request);
    }

    private function normalizeIp(string $ip): string
    {
        return Str::lower(trim($ip));
    }

    private function unauthorized()
    {
        return response()->json([
            'message' => __('messages.auth.unauthorized'),
        ], 401);
    }
}


