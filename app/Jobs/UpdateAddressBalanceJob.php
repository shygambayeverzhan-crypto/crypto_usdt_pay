<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Models\Address;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAddressBalanceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $addressId,
    ) {
        $this->onQueue('addresses');
    }

    public function handle(BlockchainServiceContract $blockchain, MoneyServiceContract $money): void
    {
        $address = Address::query()->find($this->addressId);
        if (!$address) {
            return;
        }

        $network = $address->network instanceof Network ? $address->network : Network::tryFrom((string) $address->network);
        $currency = $address->currency instanceof Currency ? $address->currency : Currency::tryFrom((string) $address->currency);

        if (!$network || !$currency) {
            return;
        }

        $balance = $blockchain->getAddressBalance($network, $currency, $address->address);
        $minor = $money->toMinor($balance);

        $address->update([
            'balance' => $minor,
            'last_checked_at' => now(),
        ]);
    }
}


