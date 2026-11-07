<?php

declare(strict_types=1);

namespace App\Contracts\Blockchain;

use App\Enums\Currency;
use App\Enums\Network;

interface ExplorerServiceContract
{
    /** Вернуть URL обозревателя транзакции для указанной сети/валюты и txid или null, если не поддерживается */
    public function getTransactionUrl(Network $network, Currency $currency, string $txid): ?string;
}


