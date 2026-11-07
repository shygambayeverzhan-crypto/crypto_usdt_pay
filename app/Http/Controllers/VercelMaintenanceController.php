<?php

namespace App\Http\Controllers;

use App\Services\Invoice\PaymentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

final class VercelMaintenanceController
{
    public function __invoke(Request $request, string $task)
    {
        $secret = (string) config('vercel.cron_secret');
        abort_unless(config('vercel.enabled') && strlen($secret) >= 32
            && hash_equals($secret, (string) $request->bearerToken()), 401);
        if ($task === 'payments') {
            $result = app(PaymentBatch::class)->run();

            return response()->json($result, $result['failed'] ? 503 : 200)
                ->header('Cache-Control', 'no-store');
        }
        abort_unless($task === 'queue', 404);
        $lock = Cache::lock('vercel:queue-tick', 60);
        if (! $lock->get()) {
            return response()->json(['status' => 'busy'])->header('Cache-Control', 'no-store');
        }
        try {
            // Fixed command, no request input. Work is awaited within this HTTP invocation.
            $process = new Process([PHP_BINDIR.'/php', base_path('artisan'), 'queue:work', 'database',
                '--queue=notifications,default', '--stop-when-empty', '--max-jobs=10',
                '--max-time=20', '--timeout=15', '--tries=3', '--sleep=0'], base_path());
            $process->setTimeout(30);
            $process->run();

            return response()->json(['status' => $process->isSuccessful() ? 'ok' : 'error'],
                $process->isSuccessful() ? 200 : 503)->header('Cache-Control', 'no-store');
        } finally {
            $lock->release();
        }
    }
}
