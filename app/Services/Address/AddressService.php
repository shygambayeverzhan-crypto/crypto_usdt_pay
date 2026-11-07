<?php

declare(strict_types=1);

namespace App\Services\Address;

use App\Contracts\Address\AddressServiceContract;
use App\Enums\Currency;
use App\Enums\Network;
use App\Enums\NetworkCurrency;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\User;
use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Services\Money\MoneyAmount;
use App\Exceptions\Address\CurrencyNetworkMismatchException;
use App\Exceptions\Address\DuplicateAddressException;
use App\Exceptions\Address\InvalidBalanceFormatException;
use App\Exceptions\Address\AddressNotFoundOnBlockchainException;
use App\Exceptions\Address\UnsupportedCurrencyException;
use App\Exceptions\Address\UnsupportedNetworkException;
use App\Exceptions\Address\NoAvailableAddressException;
use App\Enums\InvoiceStatus;
use App\Exceptions\Blockchain\TokenContractNotFoundException;
use Carbon\Carbon;

class AddressService implements AddressServiceContract
{
    public function __construct(
        private readonly BlockchainServiceContract $blockchain,
        private readonly MoneyServiceContract $money,
    ) {
    }

    public function create(User $user, string $currency, string $network, string $address): Address
    {
        $currencyEnum = Currency::tryFrom(trim($currency));
        $networkEnum = Network::tryFrom(trim($network));

        if (!$currencyEnum) {
            throw new UnsupportedCurrencyException(__('messages.addresses.errors.unsupported_currency_value', [
                'currency' => $currency,
            ]));
        }
        if (!$networkEnum) {
            throw new UnsupportedNetworkException(__('messages.addresses.errors.unsupported_network_value', [
                'network' => $network,
            ]));
        }

        // Проверка совместимости валюты и сети
        $allowedNetworks = NetworkCurrency::networksByCurrency($currencyEnum);
        if (!in_array($networkEnum, $allowedNetworks, true)) {
            throw new CurrencyNetworkMismatchException(__('messages.addresses.errors.currency_mismatch_value', [
                'currency' => $currencyEnum->value,
                'network' => $networkEnum->value,
            ]));
        }

        $exists = Address::query()
            ->where('user_id', $user->id)
            ->where('currency', $currencyEnum->value)
            ->where('network', $networkEnum->value)
            ->where('address', $address)
            ->exists();
        if ($exists) {
            throw new DuplicateAddressException(__('messages.addresses.errors.duplicate'));
        }

        // Получаем баланс адреса из блокчейн-сервиса
        try {
            $balance = $this->blockchain->getAddressBalance($networkEnum, $currencyEnum, $address);
        } catch (TokenContractNotFoundException $e) {
            // Отсутствие токена/баланса трактуем как несуществующий адрес для нужной валюты
            throw new AddressNotFoundOnBlockchainException(__('messages.addresses.errors.not_exist_blockchain'));
        }

        $balanceMinor = $this->money->toMinor($balance);

        return Address::query()->create([
            'user_id' => $user->id,
            'currency' => $currencyEnum,
            'network' => $networkEnum,
            'address' => $address,
            'is_active' => true,
            'balance' => $balanceMinor,
            'last_checked_at' => Carbon::now(),
        ]);
    }

    public function enable(Address $address): Address
    {
        $address->update(['is_active' => true]);
        return $address->refresh();
    }

    public function disable(Address $address): Address
    {
        $address->update(['is_active' => false]);
        return $address->refresh();
    }

    public function pickForPayment(User $user, Currency $currency, Network $network, MoneyAmount $amount): Address
    {
        if (!NetworkCurrency::isSupported($currency, $network)) {
            throw new CurrencyNetworkMismatchException(__('messages.addresses.errors.currency_mismatch_value', [
                'currency' => $currency->value,
                'network' => $network->value,
            ]));
        }

        // Сумма уже MoneyAmount: приводим к минорным единицам для сравнения с БД
        $amountMinor = $this->money->toMinor($amount);

        $activeStatuses = InvoiceStatus::active();

        $activeCountSub = Invoice::query()
            ->selectRaw('count(*)')
            ->whereColumn('invoices.address_id', 'addresses.id')
            ->where('invoices.user_id', $user->id)
            ->whereIn('status', $activeStatuses);

        $candidate = Address::query()
            ->select('*')
            ->selectSub($activeCountSub, 'active_invoices_count')
            ->where('user_id', $user->id)
            ->where('currency', $currency->value)
            ->where('network', $network->value)
            ->where('is_active', true)
            ->whereNotExists(function ($q) use ($amountMinor, $activeStatuses) {
                $q->selectRaw('1')
                    ->from('invoices')
                    ->whereColumn('invoices.address_id', 'addresses.id')
                    ->whereColumn('invoices.user_id', 'addresses.user_id')
                    ->whereIn('invoices.status', $activeStatuses)
                    ->where('invoices.amount', $amountMinor);
            })
            ->orderBy('active_invoices_count')
            ->orderBy('last_checked_at')
            ->orderBy('id')
            ->first();

        if (!$candidate) {
            throw new NoAvailableAddressException(__('messages.addresses.errors.no_available'));
        }

        return $candidate;
    }

    public function updateChecked(Address $address, string $balance): Address
    {
        if (!$this->isValidDecimalString($balance)) {
            throw new InvalidBalanceFormatException(__('messages.addresses.errors.invalid_balance'));
        }
        $address->update([
            'balance' => ltrim($balance, '+'),
            'last_checked_at' => Carbon::now(),
        ]);
        return $address->refresh();
    }

    private function isValidDecimalString(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        return (bool) preg_match('/^\d+(?:\.\d+)?$/', $value);
    }
}


