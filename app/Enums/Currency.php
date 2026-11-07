<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case USDT = 'usdt';
    #case TRX = 'trx';

    /** Доступные сети для данной валюты */
    public function availableNetworks(): array
    {
        return NetworkCurrency::networksByCurrency($this);
    }
}


