<?php

declare(strict_types=1);

namespace App\Services\Blockchain\Explorer;

use App\Contracts\Blockchain\ExplorerServiceContract;
use App\Enums\Currency;
use App\Enums\Network;

class ExplorerService implements ExplorerServiceContract
{
    public function getTransactionUrl(Network $network, Currency $currency, string $txid): ?string
    {
        $baseUrl = rtrim((string) config('services.tronscan.base_url'), '/');
        if ($baseUrl === '') {
            return null;
        }
        return match (true) {
            $network === Network::TRON && $currency === Currency::USDT => $baseUrl."/#/transaction/{$txid}",
            default => null,
        };
    }
}


