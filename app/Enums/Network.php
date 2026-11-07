<?php

declare(strict_types=1);

namespace App\Enums;

enum Network: string
{
    case TRON = 'tron';

    /** Доступные валюты для данной сети */
    public function availableCurrencies(): array
    {
        return NetworkCurrency::currenciesByNetwork($this);
    }
}


