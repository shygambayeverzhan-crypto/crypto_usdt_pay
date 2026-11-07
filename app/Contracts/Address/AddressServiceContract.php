<?php

declare(strict_types=1);

namespace App\Contracts\Address;

use App\Models\Address;
use App\Models\User;
use App\Enums\Currency;
use App\Enums\Network;
use App\Services\Money\MoneyAmount;

interface AddressServiceContract
{
    /**
     * Создать адрес с валидацией соответствия сети и валюты.
     * По умолчанию: is_active=true, balance='0', last_checked_at=null.
     */
    public function create(User $user, string $currency, string $network, string $address): Address;

    /** Включить адрес */
    public function enable(Address $address): Address;

    /** Отключить адрес */
    public function disable(Address $address): Address;

    /** Обновить баланс и выставить время последней проверки = now() */
    public function updateChecked(Address $address, string $balance): Address;

    /**
     * Выбрать активный адрес для оплаты по заданной сумме с ротацией.
     * Адрес подбирается среди активных адресов той же валюты/сети,
     * где нет активного инвойса с такой же суммой (уникальность суммы на адресе).
     */
    public function pickForPayment(User $user, Currency $currency, Network $network, MoneyAmount $amount): Address;
}


