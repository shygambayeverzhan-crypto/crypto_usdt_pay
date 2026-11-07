<?php

declare(strict_types=1);

namespace App\Services\Blockchain;

use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Contracts\Money\MoneyServiceContract;
use Illuminate\Support\Facades\Http;
use App\Services\Money\MoneyAmount;
use App\Exceptions\Blockchain\UnsupportedNetworkException;
use App\Exceptions\Blockchain\UnsupportedCurrencyException;
use App\Exceptions\Blockchain\ApiRequestException;
use App\Exceptions\Blockchain\TokenContractNotFoundException;

class BlockchainService implements BlockchainServiceContract
{
    // Все URL/контракты берутся из конфигурации services.trongrid.* с
    // автопереключением Mainnet/Nile через ENV (см. config/services.php)

    public function getTransactionInfoByHash(Network $network, Currency $currency, string $txid): array
    {
        if ($network !== Network::TRON) {
            throw new UnsupportedNetworkException(__('messages.blockchain.only_tron'));
        }

        if ($currency === Currency::USDT) {
            return $this->getTrc20TransactionByHash($txid);
        }

        throw new UnsupportedCurrencyException(__('messages.blockchain.only_usdt'));
    }

    public function getAddressBalance(Network $network, Currency $currency, string $address): MoneyAmount
    {
        $money = app(MoneyServiceContract::class);

        if ($network !== Network::TRON) {
            throw new UnsupportedNetworkException(__('messages.blockchain.only_tron'));
        }
        if ($currency === Currency::USDT) {
            $minor = $this->getTrc20BalanceMinorByContract($address);
            return $money->fromMinor($minor, Currency::USDT);
        }

        throw new UnsupportedCurrencyException(__('messages.blockchain.only_usdt'));
    }

    public function getIncomingTransactions(Network $network, Currency $currency, string $address): array
    {
        if ($network !== Network::TRON) {
            throw new UnsupportedNetworkException(__('messages.blockchain.only_tron'));
        }
        if ($currency !== Currency::USDT) {
            throw new UnsupportedCurrencyException(__('messages.blockchain.only_usdt'));
        }

        $url = rtrim($this->getApiBaseUrl(), '/') . "/v1/accounts/{$address}/transactions/trc20";

        $response = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->get($url, [
                'contract_address' => $this->getUsdtContractAddress(),
                'only_confirmed' => true,
                'limit' => 30,
            ]);

        if (!$response->successful()) {
            throw ApiRequestException::forHttpStatus($response->status(), $url, $response->body());
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['data'] ?? []) : [];
        if (!is_array($data)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_data'));
        }

        $result = [];
        $money = app(MoneyServiceContract::class);
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $to = (string) ($item['to'] ?? '');
            if ($to === '' || strcasecmp($to, $address) !== 0) {
                continue; // оставляем только входящие
            }

            $rawAmountMinor = (string) ($item['value'] ?? '0');
            $amountHuman = $money->format($money->fromMinor($rawAmountMinor, Currency::USDT));

            $result[] = [
                'txid' => (string) ($item['transaction_id'] ?? ($item['transactionId'] ?? '')),
                'from' => (string) ($item['from'] ?? ''),
                'to' => $to,
                'amount' => $amountHuman,
                'timestamp' => (int) ($item['block_timestamp'] ?? 0),
            ];
        }

        return $result;
    }

    private function getTrc20TransactionByHash(string $txid): array
    {
        $api = rtrim($this->getApiBaseUrl(), '/');
        $eventsUrl = $api . "/v1/transactions/{$txid}/events";
        $txUrl = $api . "/v1/transactions/{$txid}";

        $eventsResponse = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->get($eventsUrl, [
                'only_confirmed' => true,
                'contract_address' => $this->getUsdtContractAddress(),
                'limit' => 50,
            ]);

        if (!$eventsResponse->successful()) {
            throw ApiRequestException::forHttpStatus($eventsResponse->status(), $eventsUrl, $eventsResponse->body());
        }

        $eventsPayload = $eventsResponse->json();
        $events = is_array($eventsPayload) ? ($eventsPayload['data'] ?? []) : [];
        if (!is_array($events)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_events'));
        }

        $transferEvent = null;
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $name = (string) ($event['event_name'] ?? '');
            $contractAddr = (string) ($event['contract_address'] ?? '');
            if (strcasecmp($name, 'Transfer') === 0 && strcasecmp($contractAddr, $this->getUsdtContractAddress()) === 0) {
                $transferEvent = $event;
                break;
            }
        }

        if ($transferEvent === null) {
            throw new ApiRequestException(__('messages.blockchain.trc20_transfer_missing'));
        }

        // Получаем номер блока и текущий блок (с fallback, без исключения на 404)
        $blockNumber = $this->getTransactionBlockNumber($txid);
        $currentBlock = $this->getCurrentBlockNumber();
        $confirmations = ($blockNumber > 0 && $currentBlock > 0)
            ? max(0, $currentBlock - $blockNumber)
            : 0;

        $money = app(MoneyServiceContract::class);
        $amountMinor = (string) ($transferEvent['result']['value'] ?? ($transferEvent['result']['amount'] ?? '0'));
        $from = (string) ($transferEvent['result']['from'] ?? ($transferEvent['transferFromAddress'] ?? ''));
        $to = (string) ($transferEvent['result']['to'] ?? ($transferEvent['transferToAddress'] ?? ''));
        $timestamp = (int) ($transferEvent['block_timestamp'] ?? 0);

        return [
            'txid' => $txid,
            'from' => $from,
            'to' => $to,
            'amount' => $money->format($money->fromMinor($amountMinor, Currency::USDT)),
            'timestamp' => $timestamp,
            'block' => $blockNumber,
            'current_block' => $currentBlock,
            'confirmations' => $confirmations,
        ];
    }



    private function getTransactionBlockNumber(string $txid): int
    {
        $api = rtrim($this->getApiBaseUrl(), '/');
        $txUrl = $api . "/v1/transactions/{$txid}";
        $resp = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->get($txUrl);
        if ($resp->successful()) {
            $payload = $resp->json();
            if (is_array($payload)) {
                $data = $payload['data'][0] ?? null;
                if (is_array($data)) {
                    return (int) ($data['block'] ?? ($data['blockNumber'] ?? ($data['block_number'] ?? 0)));
                }
            }
        } elseif ($resp->status() !== 404) {
            throw ApiRequestException::forHttpStatus($resp->status(), $txUrl, $resp->body());
        }

        // Fallback: Tron wallet RPC
        $base = rtrim($this->getBaseUrl(), '/');
        $walletUrl = $base . '/wallet/gettransactioninfobyid';
        $walletResp = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->asJson()
            ->post($walletUrl, ['value' => $txid]);
        if ($walletResp->successful()) {
            $payload = $walletResp->json();
            if (is_array($payload)) {
                return (int) ($payload['blockNumber'] ?? 0);
            }
        }

        return 0;
    }

    private function getCurrentBlockNumber(): int
    {
        $base = rtrim($this->getBaseUrl(), '/');
        $nowBlockUrl = $base . '/wallet/getnowblock';
        $nowResponse = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->asJson()
            ->post($nowBlockUrl, []);
        if (!$nowResponse->successful()) {
            throw ApiRequestException::forHttpStatus($nowResponse->status(), $nowBlockUrl, $nowResponse->body());
        }
        $nowPayload = $nowResponse->json();
        if (!is_array($nowPayload)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_wallet_nowblock'));
        }
        return (int) ($nowPayload['block_header']['raw_data']['number'] ?? 0);
    }

    private function getTrc20BalanceMinorByContract(string $address): string
    {
        $api = rtrim($this->getApiBaseUrl(), '/');
        $url = $api . "/v1/accounts/{$address}";

        $response = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->get($url);

        if (!$response->successful()) {
            throw ApiRequestException::forHttpStatus($response->status(), $url, $response->body());
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_account'));
        }

        $data = $payload['data'][0] ?? null;
        if (!is_array($data)) {
            throw new TokenContractNotFoundException(__('messages.blockchain.account_not_found'));
        }

        $trc20 = $data['trc20'] ?? [];
        if (!is_array($trc20)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_trc20'));
        }

        $configuredContract = strtolower(trim($this->getUsdtContractAddress()));
        foreach ($trc20 as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach ($entry as $contract => $balance) {
                if ($configuredContract !== '' && strcasecmp(strtolower((string) $contract), $configuredContract) === 0) {
                    return is_string($balance) ? $balance : (string) $balance;
                }
            }
        }

        // Если по адресу нет записей по нужному контракту — считаем баланс 0
        return '0';
    }



    private function buildHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];
        $apiKey = config('services.trongrid.api_key');
        if (!empty($apiKey)) {
            $headers['TRON-PRO-API-KEY'] = (string) $apiKey;
        }
        return $headers;
    }

    private function getBaseUrl(): string
    {
        $base = (string) (config('services.trongrid.base_url') ?? 'https://api.trongrid.io');
        $base = rtrim($base, "/\t\n\r\0\x0B");
        // Удаляем завершающий /v1 или /v1/
        if ($base !== '') {
            $trimmed = rtrim($base, '/');
            if (substr($trimmed, -3) === '/v1') {
                $base = rtrim(substr($trimmed, 0, -3), '/');
            }
        }
        // Нормализация для Nile: RPC доступен на nile.trongrid.io
        $host = parse_url($base, PHP_URL_HOST) ?: '';
        if ($host !== '' && str_contains($host, 'nile.trongrid.io')) {
            return 'https://nile.trongrid.io';
        }
        return $base;
    }

    private function getApiBaseUrl(): string
    {
        $base = $this->getBaseUrl();
        $host = parse_url($base, PHP_URL_HOST) ?: '';
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';

        // Если это Nile — используем основной хост nile.trongrid.io для /v1
        if (str_contains($host, 'nile.trongrid.io')) {
            return 'https://nile.trongrid.io';
        }

        // Если уже api.* — используем как есть
        if ($host !== '' && str_starts_with($host, 'api.')) {
            return $scheme . '://' . $host;
        }

        // Для основного домена — api.trongrid.io
        if (str_ends_with($host, 'trongrid.io')) {
            return 'https://api.trongrid.io';
        }

        // Фолбек
        return $base;
    }

    private function getUsdtContractAddress(): string
    {
        $address = (string) (config('services.trongrid.usdt_contract') ?? 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
        return trim($address);
    }
}



