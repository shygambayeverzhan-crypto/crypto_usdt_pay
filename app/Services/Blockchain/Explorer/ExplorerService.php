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
        // В дальнейшем расширим маппинги по сетям/валютам.
        return match (true) {
            $network === Network::TRON && $currency === Currency::USDT => config('services.tronscan.base_url')."/#/transaction/{$txid}",
            default => null,
        };
    }
}


