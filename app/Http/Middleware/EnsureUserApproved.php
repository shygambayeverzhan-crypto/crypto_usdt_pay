<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($user->approved_at !== null) {
            return $next($request);
        }

        if (
            $request->routeIs('dashboard')
            || $request->routeIs('locale.switch')
            || $request->routeIs('logout')
            || $request->routeIs('impersonate.leave')
            || $request->is('logout')
        ) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', __('messages.auth.pending_approval'));
    }
}


