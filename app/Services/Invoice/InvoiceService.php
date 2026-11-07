<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Contracts\AppSettings\AppSettingsServiceContract;
use App\Contracts\Blockchain\BlockchainServiceContract;
use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Models\Address;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Blockchain\TronAddress;
use App\Services\Money\MoneyAmount;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService implements InvoiceServiceContract
{
    public function __construct(
        private readonly MoneyServiceContract $money,
        private readonly BlockchainServiceContract $blockchain,
        private readonly AppSettingsServiceContract $appSettings,
    ) {}

    public function create(User $user, Currency $currency, Network $network, MoneyAmount $amount, ?string $externalInvoiceId = null, ?string $callbackUrl = null, ?string $tag = null, array $metadata = [], ?Merchant $merchant = null, ?Client $client = null, ?string $productName = null, ?string $productDescription = null): Invoice
    {
        if ($currency !== Currency::USDT || $network !== Network::TRON) {
            $this->invalid('MVP supports USDT TRC-20 only.');
        }
        if (($merchant && $merchant->user_id !== $user->id) || ($client && $client->user_id !== $user->id)) {
            $this->invalid('Order belongs to another seller.');
        }
        if ($callbackUrl && ! config('payments.callbacks_enabled')) {
            $this->invalid('Callbacks are disabled. Use the authenticated status API.');
        }
        $minor = $this->money->toMinor($amount);
        if (BigInteger::of($minor)->isLessThanOrEqualTo(0)) {
            $this->invalid('Amount must be positive.');
        }
        $settings = $this->appSettings->get($currency);
        if ($settings && (BigInteger::of($minor)->isLessThan($settings->min_invoice_amount_minor)
            || BigInteger::of($minor)->isGreaterThan($settings->max_invoice_amount_minor))) {
            $this->invalid('Amount is outside configured limits.');
        }
        $chain = app(\App\Services\Blockchain\BlockchainService::class)->paymentConfiguration();
        $receive = (string) config('payments.receive_address');
        TronAddress::hex($receive);

        return DB::transaction(function () use ($user, $currency, $network, $minor, $externalInvoiceId, $callbackUrl, $tag, $metadata, $merchant, $client, $productName, $productDescription, $chain, $receive, $settings) {
            // Serialize all sellers on the same owner-controlled receiving address.
            $address = Address::query()->where('address', $receive)->where('network', $network->value)
                ->where('currency', $currency->value)->where('is_active', true)->lockForUpdate()->first();
            if (Invoice::query()->whereNotNull('chain_network')->where('chain_network', '!=', $chain['network'])->exists()) {
                $this->invalid('Use a separate database for each TRON network.');
            }
            if (! $address) {
                $this->invalid('Owner must configure the active receiving address first.');
            }
            if ($externalInvoiceId !== null) {
                $existing = Invoice::query()->where('user_id', $user->id)->where('external_invoice_id', $externalInvoiceId)->first();
                if ($existing) {
                    if ($existing->requested_amount !== $minor || $existing->merchant_id !== $merchant?->id
                        || $existing->client_id !== $client?->id || $existing->callback_url !== $callbackUrl
                        || $existing->tag !== $tag || $existing->metadata != $metadata
                        || $existing->product_name !== $productName || $existing->product_description !== $productDescription) {
                        $this->invalid('external_invoice_id already exists with different order details.');
                    }

                    return $existing;
                }
            }
            $quote = BigInteger::of($minor);
            $used = DB::table('invoices')->where('address_id', $address->id)->pluck('amount')->flip();
            for ($offset = 0; $offset <= 9999; $offset++, $quote = $quote->plus(1)) {
                if (! $used->has((string) $quote)) {
                    break;
                }
            }
            if ($offset > 9999 || ($settings && $quote->isGreaterThan($settings->max_invoice_amount_minor))) {
                $this->invalid('No unique amount available within quote limits. Contact the owner.');
            }
            $invoice = Invoice::query()->create([
                'user_id' => $user->id, 'merchant_id' => $merchant?->id, 'client_id' => $client?->id,
                'external_invoice_id' => $externalInvoiceId, 'address_id' => $address->id,
                'bot_link_token' => \Illuminate\Support\Str::random(40),
                'requested_amount' => $minor, 'amount' => (string) $quote,
                'currency' => $currency, 'network' => $network, 'status' => InvoiceStatus::PENDING,
                'chain_network' => $chain['network'], 'token_contract' => $chain['contract'],
                'expires_at' => now()->addMinutes(max(1, (int) ($merchant?->invoice_expires_in_minutes ?? 30))),
                'callback_url' => $callbackUrl, 'tag' => $tag, 'metadata' => $metadata,
                'product_name' => $productName, 'product_description' => $productDescription,
            ]);
            $this->audit($invoice, 'created', ['requested_minor' => $minor, 'quoted_minor' => (string) $quote]);

            return $invoice;
        }, 3);
    }

    public function expire(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status === InvoiceStatus::PENDING && now()->greaterThanOrEqualTo($locked->expires_at)) {
                $locked->status = InvoiceStatus::EXPIRED;
                $locked->save();
                $this->audit($locked, 'expired');
            }
        });
    }

    public function attachExactIncomingPayment(Invoice $invoice): ?Invoice
    {
        $invoice->refresh()->load('address');
        if (! in_array($invoice->status, [InvoiceStatus::PENDING, InvoiceStatus::EXPIRED], true)) {
            return null;
        }
        $transactions = $this->blockchain->getIncomingTransactions($invoice->network, $invoice->currency, $invoice->address->address);
        foreach ($transactions as $tx) {
            $timestamp = (int) ($tx['timestamp'] ?? 0);
            if ($timestamp < $invoice->created_at->getTimestampMs() || $timestamp > $invoice->expires_at->getTimestampMs()
                || ($tx['amount'] ?? '') !== $this->money->format($invoice->amount)) {
                continue;
            }
            $info = $this->blockchain->getTransactionInfoByHash($invoice->network, $invoice->currency, $tx['txid']);
            if (! $this->matches($invoice, $info)) {
                continue;
            }

            return DB::transaction(function () use ($invoice, $info) {
                $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
                if (! in_array($locked->status, [InvoiceStatus::PENDING, InvoiceStatus::EXPIRED], true)) {
                    return null;
                }
                if (Invoice::query()->where('network', $locked->network->value)->where('txid', $info['txid'])->exists()) {
                    return null;
                }
                $locked->fill([
                    'txid' => $info['txid'], 'event_index' => $info['transfers'][0]['event_index'],
                    'block_number' => $info['block'], 'amount_received' => $locked->amount,
                    'confirmations' => $info['confirmations'], 'status' => InvoiceStatus::PROCESSING,
                ])->save();
                $this->audit($locked, 'detected', ['txid' => $info['txid']]);

                return $locked;
            });
        }

        return null;
    }

    public function finalizeIfConfirmed(Invoice $invoice, int $minConfirmations = 10): bool
    {
        $invoice->refresh()->load('address');
        if ($invoice->status !== InvoiceStatus::PROCESSING || ! $invoice->txid) {
            return $invoice->status === InvoiceStatus::PAID;
        }
        $info = $this->blockchain->getTransactionInfoByHash($invoice->network, $invoice->currency, $invoice->txid);
        if (! $this->matches($invoice, $info) || ($info['txid'] ?? '') !== $invoice->txid
            || ($info['transfers'][0]['event_index'] ?? null) !== $invoice->event_index
            || ($info['block'] ?? null) !== $invoice->block_number) {
            return false;
        }

        return DB::transaction(function () use ($invoice, $info, $minConfirmations) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== InvoiceStatus::PROCESSING || $locked->txid !== $info['txid']) {
                return $locked->status === InvoiceStatus::PAID;
            }
            $locked->confirmations = $info['confirmations'];
            if ($info['confirmations'] >= max(20, $minConfirmations, (int) config('payments.min_confirmations'))) {
                $locked->status = InvoiceStatus::PAID;
                $locked->paid_at = now();
                $this->audit($locked, 'paid', ['txid' => $info['txid'], 'block' => $info['block'], 'confirmations' => $info['confirmations']]);
            }
            $locked->save();

            return $locked->status === InvoiceStatus::PAID;
        });
    }

    public function updateStatusManually(Invoice $invoice, InvoiceStatus $status, ?string $txid = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $status) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($status !== InvoiceStatus::CANCELLED || $locked->status !== InvoiceStatus::PENDING) {
                $this->invalid('Only cancellation of a pending invoice is allowed. Payment is verified by TRON.');
            }
            $locked->status = InvoiceStatus::CANCELLED;
            $locked->save();
            $this->audit($locked, 'cancelled');

            return $locked;
        });
    }

    private function matches(Invoice $invoice, array $info): bool
    {
        if (($info['solidified'] ?? false) !== true || ($info['chain_network'] ?? null) !== $invoice->chain_network
            || ! $invoice->chain_network || ($info['contract'] ?? null) !== $invoice->token_contract
            || count($info['transfers'] ?? []) !== 1) {
            return false;
        }
        $transfer = $info['transfers'][0];

        return $transfer['to_hex'] === TronAddress::hex($invoice->address->address)
            && $transfer['amount_minor'] === $this->money->toMinor($invoice->amount)
            && $info['timestamp'] >= $invoice->created_at->getTimestampMs()
            && $info['timestamp'] <= $invoice->expires_at->getTimestampMs();
    }

    private function audit(Invoice $invoice, string $event, array $details = []): void
    {
        DB::table('payment_audit')->insert([
            'invoice_id' => $invoice->id, 'event' => $event,
            'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['payment' => $message]);
    }
}
