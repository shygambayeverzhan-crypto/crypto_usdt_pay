<?php

namespace App\Http\Middleware;

use App\Support\DeploymentReadiness;
use Closure;
use Illuminate\Http\Request;

final class EnsureDeploymentConfigured
{
    public function handle(Request $request, Closure $next)
    {
        if (config('vercel.enabled') && ! $request->is('up', 'ready')) {
            if (app(DeploymentReadiness::class)->configurationErrors()) {
                return response()->view('deployment-pending', [], 503)->header('Cache-Control', 'no-store');
            }
        }

        return $next($request);
    }
}
