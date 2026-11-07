<?php

declare(strict_types=1);

namespace App\Contracts\Blockchain\Networks;

use App\Enums\Currency;
use App\Services\Money\MoneyAmount;

interface BlockchainNetworkServiceContract
{
    /** Уникальный ключ сети, например: 'tron' */
    public function getNetworkKey(): string;

    /** Получить баланс адреса по валюте в конкретной сети */
    public function getAddressBalance(Currency $currency, string $address): MoneyAmount;
}


