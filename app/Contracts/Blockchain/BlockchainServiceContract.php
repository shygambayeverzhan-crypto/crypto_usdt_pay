<?php

declare(strict_types=1);

namespace App\Contracts\Blockchain;

use App\Services\Money\MoneyAmount;
use App\Enums\Network;
use App\Enums\Currency;

interface BlockchainServiceContract
{
    /** Получить баланс адреса для указанной сети и валюты */
    public function getAddressBalance(Network $network, Currency $currency, string $address): MoneyAmount;

    /** Получить входящие транзакции для адреса (без пагинации) */
    public function getIncomingTransactions(Network $network, Currency $currency, string $address): array;

    /** Получить информацию о транзакции по TX-хэшу */
    public function getTransactionInfoByHash(Network $network, Currency $currency, string $txid): array;
}


