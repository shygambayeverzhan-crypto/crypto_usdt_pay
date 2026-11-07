<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dev;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallbackSandboxController
{
    public function __invoke(Request $request): JsonResponse
    {
        $forcedStatus = (int) $request->query('status', 0);
        $minDelayMs = (int) $request->query('min_delay_ms', 50);
        $maxDelayMs = (int) $request->query('max_delay_ms', 500);

        if ($maxDelayMs < $minDelayMs) {
            $maxDelayMs = $minDelayMs;
        }

        $delayMs = random_int($minDelayMs, $maxDelayMs);
        usleep($delayMs * 1000);

        if ($forcedStatus > 0) {
            $status = $forcedStatus;
        } else {
            $roll = random_int(1, 100);
            $status = $roll <= 70 ? 200 : ($roll <= 85 ? 400 : 500);
        }

        $response = [
            'ok' => true,
            'status' => 200,
            'received_at' => now()->toISOString(),
            'delay_ms' => $delayMs,
            'request' => [
                'headers' => $request->headers->all(),
                'query' => $request->query(),
                'payload' => $request->all(),
            ],
        ];

        return response()->json($response, $status);
    }
}


