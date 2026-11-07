<?php

use App\Contracts\Invoice\InvoiceServiceContract;
use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\Network;
use App\Models\Address;
use App\Models\ApiToken;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Blockchain\BlockchainService;
use App\Services\Blockchain\TronAddress;
use App\Services\Blockchain\TronReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.trongrid.network' => 'mainnet', 'services.trongrid.base_url' => 'https://api.trongrid.io',
        'services.trongrid.usdt_contract' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
        // Public zero address is a test fixture only, never a deployment default.
        'payments.receive_address' => 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb',
        'payments.min_confirmations' => 20, 'payments.callbacks_enabled' => false,
    ]);
    Queue::fake();
    Http::preventStrayRequests();
    $this->user = User::factory()->create(['approved_at' => now()]);
    Address::create(['user_id' => $this->user->id, 'network' => 'tron', 'currency' => 'usdt',
        'address' => config('payments.receive_address'), 'is_active' => true]);
    $this->service = app(InvoiceServiceContract::class);
    $this->money = app(MoneyServiceContract::class);
});

function paymentOrder($test, ?User $seller = null, string $external = 'order-1'): Invoice
{
    return $test->service->create($seller ?? $test->user, Currency::USDT, Network::TRON,
        $test->money->parse('10', Currency::USDT), $external);
}

function paymentChain(Invoice $invoice, array $changes = [], int $head = 119): void
{
    $receipt = array_replace_recursive([
        'id' => str_repeat('a', 64), 'blockNumber' => 100,
        'blockTimeStamp' => $invoice->created_at->getTimestampMs() + 1000,
        'receipt' => ['result' => 'SUCCESS'],
        'log' => [[
            'address' => 'a614f803b6fd780986a42c78ec9c7f77e6ded13c',
            'topics' => [TronReceipt::TRANSFER, str_repeat('0', 24).str_repeat('1', 40), str_repeat('0', 64)],
            'data' => str_pad(dechex((int) $invoice->getRawOriginal('amount')), 64, '0', STR_PAD_LEFT),
        ]],
    ], $changes);
    Http::fake([
        'api.trongrid.io/v1/accounts/*' => Http::response(['success' => true, 'data' => [[
            'to' => config('payments.receive_address'), 'transaction_id' => str_repeat('a', 64),
            'token_info' => ['address' => config('services.trongrid.usdt_contract'), 'decimals' => 6],
            'value' => $invoice->getRawOriginal('amount'), 'block_timestamp' => $invoice->created_at->getTimestampMs() + 1000,
        ]]]),
        'api.trongrid.io/walletsolidity/gettransactioninfobyid' => Http::response($receipt),
        'api.trongrid.io/walletsolidity/getnowblock' => Http::response(['block_header' => ['raw_data' => ['number' => $head]]]),
    ]);
}

it('uses checksum-aware case-sensitive TRON addresses', function () {
    expect(TronAddress::hex(config('services.trongrid.usdt_contract')))->toBe('a614f803b6fd780986a42c78ec9c7f77e6ded13c');
    expect(fn () => TronAddress::hex(strtolower(config('services.trongrid.usdt_contract'))))->toThrow(InvalidArgumentException::class);
});

it('allocates permanent unique quotes across sellers and cancelled orders', function () {
    $a = paymentOrder($this);
    $this->service->updateStatusManually($a, InvoiceStatus::CANCELLED);
    $b = paymentOrder($this, User::factory()->create(), 'other');
    expect($a->getRawOriginal('amount'))->toBe('10000000');
    expect($b->getRawOriginal('amount'))->toBe('10000001');
    expect($b->requested_amount)->toBe('10000000');
});

it('returns the same invoice for a repeated seller order id', function () {
    expect(paymentOrder($this)->id)->toBe(paymentOrder($this)->id);
    expect(Invoice::count())->toBe(1);
});

it('rejects an idempotency key with different details', function () {
    paymentOrder($this);
    $this->service->create($this->user, Currency::USDT, Network::TRON, $this->money->parse('11', Currency::USDT), 'order-1');
})->throws(\Illuminate\Validation\ValidationException::class);

it('confirms only a successful exact solidified transfer and records it once', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $this->service->attachExactIncomingPayment($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PROCESSING);
    expect($this->service->finalizeIfConfirmed($invoice))->toBeTrue();
    expect($this->service->finalizeIfConfirmed($invoice))->toBeTrue();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    expect(DB::table('payment_audit')->where('event', 'paid')->count())->toBe(1);
});

it('refuses unsafe receipts', function (array $changes) {
    $invoice = paymentOrder($this);
    paymentChain($invoice, $changes);
    $this->service->attachExactIncomingPayment($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PENDING);
})->with([
    'failed execution' => [['receipt' => ['result' => 'REVERT']]],
    'wrong hash' => [['id' => str_repeat('b', 64)]],
    'wrong recipient' => [['log' => [['topics' => [2 => str_repeat('1', 64)]]]]],
    'wrong amount' => [['log' => [['data' => str_repeat('0', 64)]]]],
    'fake token' => [['log' => [['address' => str_repeat('b', 40)]]]],
    'old transfer' => [['blockTimeStamp' => 1000]],
    'wrong event' => [['log' => [['topics' => [0 => str_repeat('b', 64)]]]]],
    'late transfer' => [['blockTimeStamp' => 9999999999999]],
]);

it('waits for the configured confirmations', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice, [], 118);
    $this->service->attachExactIncomingPayment($invoice);
    expect($this->service->finalizeIfConfirmed($invoice, 1))->toBeFalse();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PROCESSING);
});

it('rechecks the receipt instead of trusting the attached transaction', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $this->service->attachExactIncomingPayment($invoice);
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.trongrid.io/walletsolidity/gettransactioninfobyid' => Http::response([])]);
    expect($this->service->finalizeIfConfirmed($invoice))->toBeFalse();
});

it('finishes a timely transfer after the invoice deadline', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $this->service->attachExactIncomingPayment($invoice);
    $this->travel(31)->minutes();
    expect($this->service->finalizeIfConfirmed($invoice))->toBeTrue();
});

it('recovers a timely payment discovered after expiry', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $this->travel(31)->minutes();
    $this->service->expire($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::EXPIRED);
    $this->service->attachExactIncomingPayment($invoice);
    expect($this->service->finalizeIfConfirmed($invoice))->toBeTrue();
});

it('rejects manual paid status', function () {
    $this->service->updateStatusManually(paymentOrder($this), InvoiceStatus::PAID, str_repeat('a', 64));
})->throws(\Illuminate\Validation\ValidationException::class);

it('enforces unique payment hashes at database level', function () {
    $a = paymentOrder($this);
    $b = paymentOrder($this, external: 'order-2');
    $a->forceFill(['txid' => str_repeat('a', 64)])->saveQuietly();
    $b->forceFill(['txid' => str_repeat('a', 64)])->saveQuietly();
})->throws(\Illuminate\Database\QueryException::class);

it('rejects a provider URL for a different network', function () {
    config(['services.trongrid.base_url' => 'https://nile.trongrid.io']);
    paymentOrder($this);
})->throws(RuntimeException::class);

it('rejects a fake mainnet token', function () {
    config(['services.trongrid.usdt_contract' => config('payments.receive_address')]);
    paymentOrder($this);
})->throws(RuntimeException::class);

it('protects seller scope and rejects callback tokens as API credentials', function () {
    $invoice = paymentOrder($this);
    $other = User::factory()->create(['approved_at' => now()]);
    $token = ApiToken::create(['user_id' => $other->id, 'name' => ApiToken::NAME_DEFAULT, 'token' => 'test-only']);
    $this->withHeader('X-Api-Key', $token->token)->getJson('/api/v1/invoices/'.$invoice->id)->assertNotFound();
    $token->update(['name' => ApiToken::NAME_CALLBACK]);
    $this->withHeader('X-Api-Key', $token->token)->getJson('/api/v1/invoices')->assertUnauthorized();
});

it('does not expose customer data on public payment URLs', function () {
    $invoice = paymentOrder($this);
    $this->getJson('/pay/'.$invoice->id.'/data')->assertOk()->assertJsonMissingPath('metadata')
        ->assertJsonMissingPath('client')->assertJsonMissingPath('callback_url')->assertJsonMissingPath('telegram_url');
});

it('denies an unapproved seller and untrusted Telegram webhook', function () {
    $this->user->forceFill(['approved_at' => null])->save();
    ApiToken::create(['user_id' => $this->user->id, 'name' => ApiToken::NAME_DEFAULT, 'token' => 'test-only']);
    $this->withHeader('X-Api-Key', 'test-only')->getJson('/api/v1/invoices')->assertUnauthorized();
    $this->postJson('/telegram/webhook', [])->assertForbidden();
});

it('recovers the workflow from the database without queued jobs', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $this->artisan('payments:reconcile')->assertSuccessful();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

it('binds a customer link to one chat and allows an idempotent retry', function () {
    $invoice = paymentOrder($this);
    $links = app(\App\Services\Telegram\CustomerPaymentLink::class);
    expect($links->bind($invoice->bot_link_token, '100')->id)->toBe($invoice->id);
    expect($links->bind($invoice->bot_link_token, '100')->id)->toBe($invoice->id);
    expect($links->bind($invoice->bot_link_token, '200'))->toBeNull();
    expect($links->bind($invoice->id, '200'))->toBeNull();
    expect($invoice->fresh()->customer_telegram_chat_id)->toBe('100');
});

it('makes seller Telegram links single use', function () {
    $service = app(\App\Services\Telegram\TelegramService::class);
    $account = $service->refreshLink($this->user);
    $token = $account->link_token;
    $service->handleStart($token, [], '100');
    expect($account->fresh()->chat_id)->toBe('100');
    $service->handleStart($token, [], '200');
})->throws(\App\Services\Telegram\Exceptions\TelegramStartTokenInvalidException::class);

it('expires seller Telegram links', function () {
    $service = app(\App\Services\Telegram\TelegramService::class);
    $account = $service->refreshLink($this->user);
    $this->travel(16)->minutes();
    $service->handleStart($account->link_token, [], '100');
})->throws(\App\Services\Telegram\Exceptions\TelegramStartTokenInvalidException::class);

it('does not mark an invoice paid when the provider fails', function () {
    $invoice = paymentOrder($this);
    Http::fake(['api.trongrid.io/*' => Http::response([], 429)]);
    $this->artisan('payments:reconcile')->assertFailed();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PENDING);
});

it('follows history pagination on the same trusted host', function () {
    Http::fake(['api.trongrid.io/v1/accounts/*' => Http::sequence()
        ->push(['success' => true, 'data' => [], 'meta' => ['fingerprint' => 'next-page']])
        ->push(['success' => true, 'data' => []])]);
    app(BlockchainService::class)->getIncomingTransactions(Network::TRON, Currency::USDT, config('payments.receive_address'));
    Http::assertSentCount(2);
    Http::assertSent(fn ($r) => ($r['fingerprint'] ?? '') === 'next-page');
});

it('rejects multiple USDT transfers within one transaction', function () {
    $invoice = paymentOrder($this);
    $extra = ['address' => 'a614f803b6fd780986a42c78ec9c7f77e6ded13c',
        'topics' => [TronReceipt::TRANSFER, str_repeat('0', 64), str_repeat('0', 64)],
        'data' => str_repeat('0', 63).'1'];
    paymentChain($invoice, ['log' => [1 => $extra]]);
    $this->service->attachExactIncomingPayment($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PENDING);
});

it('keeps notification retries separate from the paid state', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    $invoice->forceFill(['customer_telegram_chat_id' => '100'])->save();
    $telegram = Mockery::mock();
    $telegram->shouldReceive('sendMessage')->once()->andThrow(new RuntimeException('Temporary Telegram error'));
    \Telegram\Bot\Laravel\Facades\Telegram::swap($telegram);
    $this->artisan('payments:reconcile')->assertFailed();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    expect($invoice->fresh()->customer_notified_at)->toBeNull();
});

it('rejects missing and incorrect maintenance secrets before touching payments', function () {
    $invoice = paymentOrder($this);
    config(['vercel.enabled' => true, 'vercel.cron_secret' => str_repeat('x', 40),
        'database.connections.pgsql.url' => 'postgresql://fixture/test']);
    $this->getJson('/internal/payments/tick')->assertUnauthorized();
    $this->withToken('wrong')->getJson('/internal/payments/tick')->assertUnauthorized();
    config(['vercel.cron_secret' => '']);
    $this->withToken('')->getJson('/internal/payments/tick')->assertUnauthorized();
    Http::assertNothingSent();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PENDING);
});

it('runs an authenticated Vercel tick and confirms only through the receipt checks', function () {
    $invoice = paymentOrder($this);
    paymentChain($invoice);
    config(['vercel.enabled' => true, 'vercel.cron_secret' => str_repeat('x', 40),
        'database.connections.pgsql.url' => 'postgresql://fixture/test']);
    $this->withToken(str_repeat('x', 40))->getJson('/internal/payments/tick')
        ->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('checked', 1);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    $this->withToken(str_repeat('x', 40))->getJson('/internal/payments/tick')->assertOk();
    expect(DB::table('payment_audit')->where('event', 'paid')->count())->toBe(1);
});

it('serializes bounded ticks using the shared lock', function () {
    paymentOrder($this);
    $lock = \Illuminate\Support\Facades\Cache::lock('payments:bounded-reconcile', 240);
    $lock->get();
    try {
        expect(app(\App\Services\Invoice\PaymentBatch::class)->run()['status'])->toBe('busy');
        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

it('rotates bounded batches so older pending invoices do not starve new ones', function () {
    $a = paymentOrder($this);
    $b = paymentOrder($this, external: 'order-2');
    Http::fake(['api.trongrid.io/v1/accounts/*' => Http::response(['success' => true, 'data' => []])]);
    $batch = app(\App\Services\Invoice\PaymentBatch::class);
    expect($batch->run(1)['checked'])->toBe(1);
    expect(Invoice::whereNotNull('last_checked_at')->count())->toBe(1);
    expect($batch->run(1)['checked'])->toBe(1);
    expect($a->fresh()->last_checked_at)->not->toBeNull();
    expect($b->fresh()->last_checked_at)->not->toBeNull();
});

it('fails closed on provider errors and releases the batch lock for recovery', function () {
    $invoice = paymentOrder($this);
    Http::fake(['api.trongrid.io/*' => Http::response([], 429)]);
    $batch = app(\App\Services\Invoice\PaymentBatch::class);
    expect($batch->run()['status'])->toBe('error');
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PENDING);
    expect(\Illuminate\Support\Facades\Cache::get('payments:last_success'))->toBeNull();
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    paymentChain($invoice);
    expect($batch->run()['status'])->toBe('ok');
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

it('does not start blockchain I/O after the invocation deadline', function () {
    $budget = app(\App\Services\Blockchain\RequestBudget::class);
    $budget->start(-1);
    try {
        expect(fn () => app(BlockchainService::class)->getIncomingTransactions(Network::TRON, Currency::USDT,
            config('payments.receive_address')))->toThrow(\App\Services\Blockchain\BudgetExhausted::class);
        Http::assertNothingSent();
    } finally {
        $budget->clear();
    }
});

it('blocks new Vercel invoices until enabled and reconciliation is fresh', function () {
    config(['vercel.enabled' => true]);
    expect(fn () => paymentOrder($this))->toThrow(\Illuminate\Validation\ValidationException::class);
    config(['vercel.payments_enabled' => true]);
    expect(fn () => paymentOrder($this))->toThrow(\Illuminate\Validation\ValidationException::class);
    \Illuminate\Support\Facades\Cache::put('payments:last_success', now()->toIso8601String());
    $invoice = paymentOrder($this);
    $this->travel(6)->minutes();
    // Idempotent repeats still return the existing order during an outage.
    expect(paymentOrder($this)->id)->toBe($invoice->id);
    expect(fn () => paymentOrder($this, external: 'new-order'))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('serves an honest unavailable page when the Vercel database is not configured', function () {
    config(['vercel.enabled' => true, 'database.connections.pgsql.url' => null,
        'database.connections.pgsql.host' => '']);
    $this->get('/login')->assertStatus(503)->assertSee('Панель ещё настраивается');
    $this->get('/up')->assertOk();
});
