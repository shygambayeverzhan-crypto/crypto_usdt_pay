<?php

declare(strict_types=1);

namespace App\Services\Blockchain;

use Brick\Math\BigInteger;

final class TronReceipt
{
    public const TRANSFER = 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

    /** Only a successful solidified receipt is authoritative; index history is discovery only. */
    public static function transfers(array $receipt, string $txid, string $contract): array
    {
        if (($receipt['id'] ?? null) !== $txid || ($receipt['receipt']['result'] ?? null) !== 'SUCCESS'
            || ! is_int($receipt['blockNumber'] ?? null) || $receipt['blockNumber'] <= 0
            || ! is_int($receipt['blockTimeStamp'] ?? null) || $receipt['blockTimeStamp'] <= 0) {
            return [];
        }
        $result = [];
        foreach ($receipt['log'] ?? [] as $index => $log) {
            $topics = $log['topics'] ?? [];
            if (($log['address'] ?? '') !== $contract || count($topics) !== 3 || $topics[0] !== self::TRANSFER) {
                continue;
            }
            if (! preg_match('/^0{24}[0-9a-f]{40}$/D', $topics[1])
                || ! preg_match('/^0{24}[0-9a-f]{40}$/D', $topics[2])
                || ! preg_match('/^[0-9a-f]{64}$/D', $log['data'] ?? '')) {
                continue;
            }
            $result[] = [
                'event_index' => $index,
                'to_hex' => substr($topics[2], 24),
                'from_hex' => substr($topics[1], 24),
                'amount_minor' => (string) BigInteger::fromBase($log['data'], 16),
            ];
        }

        return $result;
    }
}
