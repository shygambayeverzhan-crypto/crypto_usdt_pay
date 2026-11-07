<?php

declare(strict_types=1);

namespace App\Contracts\Invoice;

use App\Enums\Currency;
use App\Enums\Network;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Money\MoneyAmount;

interface InvoiceServiceContract
{
    /**
     * Создать инвойс, выбирая адрес через AddressService::pickForPayment.
     * Возвращает созданный инвойс или бросает исключение при отсутствии доступного адреса.
     */
    public function create(User $user, Currency $currency, Network $network, MoneyAmount $amount, ?string $externalInvoiceId = null, ?string $callbackUrl = null, ?string $tag = null, array $metadata = [], ?Merchant $merchant = null, ?Client $client = null, ?string $productName = null, ?string $productDescription = null): Invoice;

    /**
     * Пометить инвойс как просроченный, если он ещё активен.
     */
    public function expire(Invoice $invoice): void;

    /**
     * Найти точный входящий платёж для инвойса и прикрепить его к инвойсу
     * (txid, amount_received, confirmations) со сменой статуса на PROCESSING.
     * Возвращает обновлённый инвойс или null, если платёж не найден.
     */
    public function attachExactIncomingPayment(Invoice $invoice): ?Invoice;

    /**
     * Обновить подтверждения по прикреплённой транзакции и, если их >= минимального порога,
     * финализировать инвойс как PAID. Возвращает true, если инвойс был финализирован.
     */
    public function finalizeIfConfirmed(Invoice $invoice, int $minConfirmations = 10): bool;

    /**
     * Ручное обновление статуса инвойса с применением бизнес-правил:
     * - если статус = paid, txid обязателен и сохраняется;
     * - если статус один из cancelled/expired/pending/processing — txid очищается;
     * - если статус любой кроме pending — amount_received и confirmations очищаются.
     */
    public function updateStatusManually(Invoice $invoice, InvoiceStatus $status, ?string $txid = null): Invoice;
}


