<?php

declare(strict_types=1);

namespace App\Services\Blockchain;

use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Exceptions\Blockchain\ApiRequestException;
use App\Exceptions\Blockchain\TokenContractNotFoundException;
use App\Exceptions\Blockchain\UnsupportedCurrencyException;
use App\Exceptions\Blockchain\UnsupportedNetworkException;
use App\Services\Money\MoneyAmount;
use Illuminate\Support\Facades\Http;

class BlockchainService implements BlockchainServiceContract
{
    private array $history = [];

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

        $this->paymentConfiguration();
        if (isset($this->history[$address]) && $this->history[$address]['until'] > time()) {
            return $this->history[$address]['data'];
        }
        TronAddress::hex($address);
        $url = $this->getApiBaseUrl()."/v1/accounts/{$address}/transactions/trc20";
        $query = [
            'contract_address' => $this->getUsdtContractAddress(), 'only_confirmed' => true,
            'only_to' => true, 'limit' => 200, 'order_by' => 'block_timestamp,asc',
            'min_timestamp' => now()->subHours((int) config('payments.reconcile_hours'))->getTimestampMs(),
        ];
        $result = [];
        $money = app(MoneyServiceContract::class);
        $seen = [];
        for ($page = 0; $page < 100; $page++) {
            $response = Http::withHeaders($this->buildHeaders())->connectTimeout(5)->timeout(15)
                ->withoutRedirecting()->get($url, $query);
            $response->throw();
            $payload = $response->json();
            if (($payload['success'] ?? null) !== true || ! is_array($payload['data'] ?? null)) {
                throw new ApiRequestException('Malformed TRON transfer history.');
            }
            foreach ($payload['data'] as $item) {
                if (($item['to'] ?? '') !== $address || ($item['token_info']['address'] ?? '') !== $this->getUsdtContractAddress()
                    || (int) ($item['token_info']['decimals'] ?? -1) !== 6
                    || ! preg_match('/^[0-9]+$/D', (string) ($item['value'] ?? ''))
                    || ! preg_match('/^[0-9a-f]{64}$/D', $item['transaction_id'] ?? '')) {
                    continue;
                }
                $result[] = [
                    'txid' => $item['transaction_id'], 'to' => $address,
                    'amount' => $money->format($money->fromMinor($item['value'], Currency::USDT)),
                    'timestamp' => (int) ($item['block_timestamp'] ?? 0),
                ];
            }
            $fingerprint = $payload['meta']['fingerprint'] ?? null;
            if (! $fingerprint) {
                $this->history[$address] = ['until' => time() + 15, 'data' => $result];

                return $result;
            }
            if (isset($seen[$fingerprint])) {
                throw new ApiRequestException('TRON pagination repeated a cursor.');
            }
            $seen[$fingerprint] = true;
            $query['fingerprint'] = $fingerprint;
        }
        throw new ApiRequestException('TRON history exceeds MVP capacity; reconciliation required.');
    }

    public function paymentConfiguration(): array
    {
        $network = (string) config('services.trongrid.network');
        $urls = ['mainnet' => 'https://api.trongrid.io', 'nile' => 'https://nile.trongrid.io'];
        if (! isset($urls[$network]) || $this->getBaseUrl() !== $urls[$network]) {
            throw new \RuntimeException('TRON network does not match the pinned provider URL.');
        }
        $contract = $this->getUsdtContractAddress();
        $hex = TronAddress::hex($contract);
        if ($network === 'mainnet' && $hex !== 'a614f803b6fd780986a42c78ec9c7f77e6ded13c') {
            throw new \RuntimeException('Mainnet contract is not the canonical USDT contract.');
        }

        return ['network' => $network, 'contract' => $contract, 'contract_hex' => $hex];
    }

    private function getTrc20TransactionByHash(string $txid): array
    {
        if (! preg_match('/^[0-9a-f]{64}$/D', $txid)) {
            throw new \InvalidArgumentException('Invalid transaction hash.');
        }
        $chain = $this->paymentConfiguration();
        $receipt = $this->rpc('/walletsolidity/gettransactioninfobyid', ['value' => $txid]);
        $transfers = TronReceipt::transfers($receipt, $txid, $chain['contract_hex']);
        if (! $transfers) {
            return ['solidified' => false, 'transfers' => []];
        }
        $head = $this->rpc('/walletsolidity/getnowblock', []);
        $height = (int) ($head['block_header']['raw_data']['number'] ?? 0);
        $block = $receipt['blockNumber'];
        if ($height < $block) {
            return ['solidified' => false, 'transfers' => []];
        }

        return [
            'txid' => $txid, 'solidified' => true, 'chain_network' => $chain['network'],
            'contract' => $chain['contract'], 'transfers' => $transfers,
            'timestamp' => $receipt['blockTimeStamp'], 'block' => $block,
            'confirmations' => $height - $block + 1,
        ];
    }

    private function rpc(string $path, array $body): array
    {
        $response = Http::withHeaders($this->buildHeaders())->connectTimeout(5)->timeout(15)
            ->withoutRedirecting()->post($this->getBaseUrl().$path, $body);
        $response->throw();
        $data = $response->json();
        if (! is_array($data)) {
            throw new \RuntimeException('Malformed TRON receipt.');
        }

        return $data;
    }

    private function getTrc20BalanceMinorByContract(string $address): string
    {
        $api = rtrim($this->getApiBaseUrl(), '/');
        $url = $api."/v1/accounts/{$address}";

        $response = Http::withHeaders($this->buildHeaders())
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            throw ApiRequestException::forHttpStatus($response->status(), $url, $response->body());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_account'));
        }

        $data = $payload['data'][0] ?? null;
        if (! is_array($data)) {
            throw new TokenContractNotFoundException(__('messages.blockchain.account_not_found'));
        }

        $trc20 = $data['trc20'] ?? [];
        if (! is_array($trc20)) {
            throw new ApiRequestException(__('messages.blockchain.malformed_trongrid_trc20'));
        }

        $configuredContract = strtolower(trim($this->getUsdtContractAddress()));
        foreach ($trc20 as $entry) {
            if (! is_array($entry)) {
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
        if (! empty($apiKey)) {
            $headers['TRON-PRO-API-KEY'] = (string) $apiKey;
        }

        return $headers;
    }

    private function getBaseUrl(): string
    {
        $base = rtrim(trim((string) config('services.trongrid.base_url')), '/');
        if ($base === '') {
            throw new \RuntimeException('Configure TRONGRID_BASE_URL before using blockchain services.');
        }
        if (str_ends_with($base, '/v1')) {
            $base = substr($base, 0, -3);
        }

        return $base;
    }

    private function getApiBaseUrl(): string
    {
        return $this->getBaseUrl();
    }

    private function getUsdtContractAddress(): string
    {
        $address = trim((string) config('services.trongrid.usdt_contract'));
        if ($address === '') {
            throw new \RuntimeException('Configure the USDT token contract for the selected network.');
        }

        return $address;
    }
}
