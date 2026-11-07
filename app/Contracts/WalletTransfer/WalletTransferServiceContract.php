<?php

declare(strict_types=1);

namespace App\Contracts\WalletTransfer;

interface WalletTransferServiceContract
{
    public function sendUsdt(string $to, string $amount, ?string $idempotencyKey = null): bool;
}


