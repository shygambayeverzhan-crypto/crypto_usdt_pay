<?php

namespace App\Http\Controllers;

use App\Support\DeploymentReadiness;

final class ReadinessController
{
    public function __invoke(DeploymentReadiness $readiness)
    {
        $ready = $readiness->errors() === [];

        return response()->json(['status' => $ready ? 'ready' : 'unavailable'], $ready ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
