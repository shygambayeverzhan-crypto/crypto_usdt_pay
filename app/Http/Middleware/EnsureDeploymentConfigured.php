<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class EnsureDeploymentConfigured
{
    public function handle(Request $request, Closure $next)
    {
        if (config('vercel.enabled') && ! $request->is('up')) {
            $database = config('database.connections.pgsql');
            $configured = config('app.key') && ($database['url']
                || ($database['host'] && $database['database'] && $database['username'] && $database['password']));
            if (! $configured) {
                return response()->view('deployment-pending', [], 503)->header('Cache-Control', 'no-store');
            }
        }

        return $next($request);
    }
}
