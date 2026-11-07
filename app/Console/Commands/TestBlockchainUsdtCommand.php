<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use Illuminate\Console\Command;
use Throwable;

class TestBlockchainUsdtCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'chain:test-usdt {address : TRON адрес (USDT TRC-20)} {--txid= : Необязательный хэш транзакции для проверки}';

    /**
     * The console command description.
     */
    protected $description = 'Тест всех эндпоинтов BlockchainService для USDT TRC-20: баланс, входящие транзакции, детали транзакции.';

    public function handle(BlockchainServiceContract $blockchain, MoneyServiceContract $money): int
    {
        $address = (string) $this->argument('address');
        $txidOpt = (string) ($this->option('txid') ?? '');

        $this->info('Тест сети TRON и валюты USDT (TRC-20)');
        $this->line('Адрес: ' . $address);

        // 1) Баланс адреса (USDT)
        $this->info('1) getAddressBalance(TRON, USDT, address)');
        try {
            $balance = $blockchain->getAddressBalance(Network::TRON, Currency::USDT, $address);
            $this->line('USDT баланс: ' . $money->format($balance) . ' USDT');
        } catch (Throwable $e) {
            $this->reportException($e, 'getAddressBalance');
        }

        // 2) Входящие транзакции (USDT)
        $this->info('2) getIncomingTransactions(TRON, USDT, address)');
        $incoming = [];
        try {
            $incoming = $blockchain->getIncomingTransactions(Network::TRON, Currency::USDT, $address);
            $this->line('Найдено входящих транзакций: ' . count($incoming));
            // Отображаем первые несколько записей
            $preview = array_slice($incoming, 0, 5);
            if (!empty($preview)) {
                dump($preview);
            }
        } catch (Throwable $e) {
            $this->reportException($e, 'getIncomingTransactions');
        }

        // 3) Детали транзакции по хэшу (USDT)
        $this->info('3) getTransactionInfoByHash(TRON, USDT, txid)');
        $txid = $txidOpt;
        if ($txid === '' && isset($incoming[0]['txid']) && is_string($incoming[0]['txid'])) {
            $txid = $incoming[0]['txid'];
            $this->line('txid не указан, использую из входящих: ' . $txid);
        }

        if ($txid === '') {
            $this->warn('Пропуск запроса деталей транзакции: не удалось определить txid. Укажите --txid=.');
        } else {
            try {
                $tx = $blockchain->getTransactionInfoByHash(Network::TRON, Currency::USDT, $txid);
                dump($tx);
            } catch (Throwable $e) {
                $this->reportException($e, 'getTransactionInfoByHash');
            }
        }

        return self::SUCCESS;
    }

    private function reportException(Throwable $e, string $context): void
    {
        $this->error('[' . $context . '] ' . get_class($e) . ': ' . $e->getMessage());
        $this->line('Файл: ' . $e->getFile() . ':' . $e->getLine());
        $code = $e->getCode();
        if ($code !== 0) {
            $this->line('Код: ' . (string) $code);
        }

        $trace = $e->getTrace();
        $maxFrames = 8;
        $this->line('Стек (первые ' . $maxFrames . ' фреймов):');
        foreach (array_slice($trace, 0, $maxFrames) as $i => $frame) {
            $class = isset($frame['class']) ? $frame['class'] : '';
            $type = isset($frame['type']) ? $frame['type'] : '';
            $function = isset($frame['function']) ? $frame['function'] : '';
            $file = isset($frame['file']) ? $frame['file'] : 'unknown';
            $line = isset($frame['line']) ? (string) $frame['line'] : '0';
            $this->line('#' . $i . ' ' . $class . $type . $function . ' at ' . $file . ':' . $line);
        }

        $prev = $e->getPrevious();
        if ($prev) {
            $this->warn('Caused by: ' . get_class($prev) . ': ' . $prev->getMessage() . ' at ' . $prev->getFile() . ':' . $prev->getLine());
        }
    }
}


