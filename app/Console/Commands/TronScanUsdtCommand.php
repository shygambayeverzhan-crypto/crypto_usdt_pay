<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Models\Invoice;
use Illuminate\Console\Command;

class TronScanUsdtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan chain:balance TJ... --network=tron --currency=USDT
     */
    protected $signature = 'chain:balance {address : Адрес в сети} {--network=tron : Сеть (например: tron)} {--currency=USDT : Валюта (например: USDT|TRX)}';

    /**
      * The console command description.
      */
    protected $description = 'Показать баланс адреса по сети и валюте';

    public function handle(BlockchainServiceContract $blockchain, MoneyServiceContract $money): int
    {
        $address = (string) $this->argument('address');
        $networkOpt = (string) $this->option('network');
        $currencyOpt = (string) $this->option('currency');

        $network = Network::tryFrom(strtolower(trim($networkOpt)));
        $currency = Currency::tryFrom(strtoupper(trim($currencyOpt)));

        if (!$network || !$currency) {
            $this->error('Некорректные параметры network/currency.');
            return self::INVALID;
        }
        $this->info("Запрос баланса: сеть={$network->value}, валюта={$currency->value}, адрес={$address}");



        $balance = $blockchain->getAddressBalance($network, $currency, $address);
        $this->line('Баланс: ' . $money->format($balance) . ' ' . $currency->value);
        $balance = $blockchain->getAddressBalance($network, Currency::TRX, $address);
        $this->line('Баланс: ' . $money->format($balance) . ' ' . Currency::TRX->value);
        $transactions = $blockchain->getIncomingTransactions($network, $currency, $address);
        dump($transactions);

        return self::SUCCESS;
    }
}


