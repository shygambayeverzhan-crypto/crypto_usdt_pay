<?php

declare(strict_types=1);

namespace App\Services\WalletTransfer;

use App\Contracts\WalletTransfer\WalletTransferServiceContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

final class WalletTransferService implements WalletTransferServiceContract
{
    private const WALLET_ID = 'test_wallet_1';
    private const NETWORK = 'nile';
    private const SCRIPT = 'send_usdt_cli.js';
    private const TIMEOUT_SECONDS = 120;

    public function sendUsdt(string $to, string $amount, ?string $idempotencyKey = null): bool
    {
        $to = trim($to);
        $amount = $this->normalizeAmount($amount);

        if ($to === '' || $amount === '') {
            Log::error('WalletTransfer: validation failed', [
                'to' => $to,
                'amount' => $amount,
            ]);

            return false;
        }

        $workingDirectory = base_path('wallet-signer');
        $scriptPath = $workingDirectory.DIRECTORY_SEPARATOR.self::SCRIPT;

        if (!is_file($scriptPath)) {
            Log::error('WalletTransfer: wallet-signer script not found', [
                'script' => $scriptPath,
            ]);

            return false;
        }

        $idempotencyKey = $idempotencyKey ?: $this->makeIdempotencyKey();

        $result = Process::path($workingDirectory)
            ->timeout(self::TIMEOUT_SECONDS)
            ->run([
                'node',
                self::SCRIPT,
                self::WALLET_ID,
                $to,
                $amount,
                $idempotencyKey,
                self::NETWORK,
            ]);

        $payload = $this->decodeJsonLine($result->output());

        if (!is_array($payload) || !array_key_exists('ok', $payload)) {
            Log::error('WalletTransfer: invalid response from node script', [
                'exit_code' => $result->exitCode(),
                'stdout' => $result->output(),
                'stderr' => $result->errorOutput(),
            ]);

            return false;
        }

        if (($payload['ok'] ?? false) === true) {
            return true;
        }

        Log::error('WalletTransfer: transfer failed', [
            'exit_code' => $result->exitCode(),
            'stdout' => $result->output(),
            'stderr' => $result->errorOutput(),
            'response' => $payload,
        ]);

        return false;
    }

    private function makeIdempotencyKey(): string
    {
        return 'order_'.Str::uuid()->toString();
    }

    private function normalizeAmount(string $amount): string
    {
        $normalized = str_replace([' ', '_', ','], ['', '', '.'], trim($amount));

        return $normalized;
    }

    private function decodeJsonLine(string $output): array|null
    {
        $output = trim($output);
        if ($output === '') {
            return null;
        }

        $lines = preg_split("/\r\n|\n|\r/", $output) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        $json = $lines === [] ? $output : end($lines);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}


