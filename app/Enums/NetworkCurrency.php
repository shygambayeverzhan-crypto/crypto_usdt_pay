<?php

declare(strict_types=1);

namespace App\Enums;

final class NetworkCurrency
{
    /** @var array<string, array<int, Currency>> */
    private const NETWORK_TO_CURRENCIES = [
        Network::TRON->value => [Currency::USDT/*, Currency::TRX*/],
    ];

    /** @var array<string, array<int, Network>> */
    private const CURRENCY_TO_NETWORKS = [
        Currency::USDT->value => [Network::TRON],
        /*Currency::TRX->value => [Network::TRON],*/
    ];

    /**
     * Получить валюты для сети
     * @return array<int, Currency>
     */
    public static function currenciesByNetwork(Network $network): array
    {
        return self::NETWORK_TO_CURRENCIES[$network->value] ?? [];
    }

    /**
     * Получить сети для валюты
     * @return array<int, Network>
     */
    public static function networksByCurrency(Currency $currency): array
    {
        return self::CURRENCY_TO_NETWORKS[$currency->value] ?? [];
    }

    /**
     * Проверить, поддерживается ли валюта на указанной сети.
     */
    public static function isSupported(Currency $currency, Network $network): bool
    {
        $networks = self::networksByCurrency($currency);
        return in_array($network, $networks, true);
    }
}


