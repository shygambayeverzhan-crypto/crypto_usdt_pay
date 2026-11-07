<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Address\AddressServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Exceptions\Invoice\InvoiceAmountOutOfRangeException;
use App\Jobs\ExpireInvoiceJob;
use App\Jobs\AttachIncomingPaymentJob;
use App\Jobs\ConfirmInvoicePaymentJob;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Money\MoneyAmount;
use Illuminate\Support\Carbon;

class InvoiceService implements InvoiceServiceContract
{
    private const DEFAULT_EXPIRES_IN_MINUTES = 30;

    public function __construct(
        private readonly AddressServiceContract $addresses,
        private readonly MoneyServiceContract $money,
        private readonly BlockchainServiceContract $blockchain,
        private readonly AppSettingsServiceContract $appSettings,
    ) {
    }

    public function create(User $user, Currency $currency, Network $network, MoneyAmount $amount, ?string $externalInvoiceId = null, ?string $callbackUrl = null, ?string $tag = null, array $metadata = [], ?Merchant $merchant = null, ?Client $client = null, ?string $productName = null, ?string $productDescription = null): Invoice
    {
        // Проверка суммы по глобальным App Settings
        $settings = $this->appSettings->get($currency);
        if ($settings !== null) {
            $min = $this->money->fromMinor($settings->min_invoice_amount_minor, $currency);
            $max = $this->money->fromMinor($settings->max_invoice_amount_minor, $currency);

            if ($this->money->compare($amount, $min) < 0) {
                throw new InvoiceAmountOutOfRangeException(__('messages.invoices.amount_below_min'));
            }
            if ($this->money->compare($amount, $max) > 0) {
                throw new InvoiceAmountOutOfRangeException(__('messages.invoices.amount_above_max'));
            }
        }

        $address = $this->addresses->pickForPayment($user, $currency, $network, $amount);

        if ($address->user_id !== $user->id) {
            throw new \RuntimeException(__('messages.addresses.errors.not_owner'));
        }

        if ($merchant !== null && $merchant->user_id !== $user->id) {
            throw new \RuntimeException(__('messages.merchants.errors.not_owner'));
        }
        if ($client !== null && $client->user_id !== $user->id) {
            throw new \RuntimeException(__('messages.clients.errors.not_owner'));
        }
        $amountMinor = $this->money->toMinor($amount);

        $expiresInMinutes = $merchant?->invoice_expires_in_minutes ?? self::DEFAULT_EXPIRES_IN_MINUTES;
        $expiresInMinutes = max(1, (int) $expiresInMinutes);
        $expiresAt = now()->addMinutes($expiresInMinutes);

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
            'merchant_id' => $merchant?->id,
            'client_id' => $client?->id,
            'external_invoice_id' => $externalInvoiceId,
            'address_id' => $address->id,
            'amount' => $amountMinor,
            'currency' => $currency,
            'network' => $network,
            'status' => InvoiceStatus::PENDING,
            'expires_at' => $expiresAt,
            'callback_url' => $callbackUrl,
            'tag' => $tag,
            'metadata' => $metadata,
            'product_name' => $productName,
            'product_description' => $productDescription,
        ]);

        // Планируем асинхронную экспирацию через N минут
        ExpireInvoiceJob::dispatch($invoice->id)->delay($expiresAt);

        // Запускаем периодический поиск точной входящей транзакции раз в минуту
        AttachIncomingPaymentJob::dispatch($invoice->id);

        return $invoice;
    }

    /**
     * Пометить инвойс как просроченный, если он ещё активен.
     */
    public function expire(Invoice $invoice): void
    {
        $invoice->refresh();

        // Просрочка допустима только из статуса PENDING
        if ($invoice->status !== InvoiceStatus::PENDING) {
            return;
        }

        $invoice->status = InvoiceStatus::EXPIRED;
        $invoice->save();
    }

    public function attachExactIncomingPayment(Invoice $invoice): ?Invoice
    {
        if ($invoice->status->isFinal()) {
            return $invoice; // уже финализирован
        }

        $tx = $this->findExactIncomingPayment($invoice);
        if ($tx === null) {
            return null;
        }

        // Обновляем поля инвойса
        $txid = (string) ($tx['txid'] ?? '');
        $amountStr = (string) ($tx['amount'] ?? '0');
        $confirmations = (int) ($tx['confirmations'] ?? 0);

        $invoice->txid = $txid;
        $invoice->amount_received = $this->money->parse($amountStr, $invoice->currency);
        $invoice->confirmations = $confirmations;
        $invoice->status = InvoiceStatus::PROCESSING; // обнаружен точный платёж, ожидаем финализацию
        $invoice->save();

        return $invoice;
    }

    /**
     * Обновить подтверждения по прикреплённой транзакции и, если их достаточно, финализировать инвойс как PAID.
     */
    public function finalizeIfConfirmed(Invoice $invoice, int $minConfirmations = 10): bool
    {
        $invoice->refresh();

        if ($invoice->status->isFinal()) {
            return true; // уже финализирован
        }

        if ($invoice->status !== InvoiceStatus::PROCESSING) {
            return false; // нет прикреплённой транзакции или неверный статус
        }

        $txid = (string) ($invoice->txid ?? '');
        if ($txid === '') {
            return false;
        }

        // Получаем актуальные данные по транзакции
        $info = $this->blockchain->getTransactionInfoByHash($invoice->network, $invoice->currency, $txid);
        $confirmations = (int) ($info['confirmations'] ?? 0);

        $invoice->confirmations = $confirmations;
        $invoice->save();

        if ($confirmations >= $minConfirmations) {
            $invoice->status = InvoiceStatus::PAID;
            $invoice->save();
            return true;
        }

        return false;
    }

    public function updateStatusManually(Invoice $invoice, InvoiceStatus $status, ?string $txid = null): Invoice
    {
        $invoice->refresh();

        // txid правила
        if ($status === InvoiceStatus::PAID) {
            $invoice->txid = (string) $txid;
        } else {
            $invoice->txid = null;
        }

        // amount_received и confirmations очищаются для любого статуса кроме pending
        if ($status !== InvoiceStatus::PENDING) {
            $invoice->amount_received = $this->money->create('0', $invoice->currency);
            $invoice->confirmations = 0;
        }

        $invoice->status = $status;
        $invoice->save();

        return $invoice;
    }
    /**
     * Найти точный входящий платёж в блокчейне для инвойса в пределах его окна оплаты.
     */
    protected function findExactIncomingPayment(Invoice $invoice): ?array
    {
        $invoice->loadMissing('address');
        $address = $invoice->address?->address;
        if ($address === null || $address === '') {
            return null;
        }

        $transactions = $this->blockchain->getIncomingTransactions($invoice->network, $invoice->currency, $address);

        $createdAt = $invoice->created_at;
        $expiresAt = $invoice->expires_at;
        if ($createdAt === null || $expiresAt === null) {
            return null;
        }

        foreach ($transactions as $tx) {
            if (!is_array($tx)) {
                continue;
            }

            $amountStr = (string) ($tx['amount'] ?? '');
            if ($amountStr === '') {
                continue;
            }

            // Парсим сумму транзакции к MoneyAmount и сверяем по точному равенству
            $txAmount = $this->money->parse($amountStr, $invoice->currency);
            if ($this->money->compare($txAmount, $invoice->amount) !== 0) {
                continue;
            }

            // TronGrid отдаёт метку времени, как правило, в миллисекундах
            $ts = (int) ($tx['timestamp'] ?? 0);
            if ($ts <= 0) {
                continue;
            }
            $txTime = $ts > 2000000000 ? Carbon::createFromTimestampMs($ts) : Carbon::createFromTimestamp($ts);

            if ($txTime->lt($createdAt) || $txTime->gt($expiresAt)) {
                continue;
            }

            $txid = (string) ($tx['txid'] ?? '');
            if ($txid === '') {
                continue;
            }

            // Уточняем данные по транзакции, включая подтверждения
            return $this->blockchain->getTransactionInfoByHash($invoice->network, $invoice->currency, $txid);
        }

        return null;
    }
}


