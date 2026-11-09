<?php

use App\Models\Address;
use App\Models\Invoice;
use App\Models\PaymentRequisite;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    $this->requisiteOwner = User::factory()->create(['approved_at' => now()]);
});

afterEach(function () {
    Http::assertNothingSent();
});

function requisiteCardPayload(array $changes = []): array
{
    return array_replace([
        'label' => 'Synthetic test card',
        'method' => 'card',
        'currency' => 'KZT',
        'bank' => 'kaspi',
        'holder' => 'SYNTHETIC TEST HOLDER',
        // Published test number; never a real customer's payment details.
        'number' => '4242424242424242',
        'min_amount' => '100.00',
        'max_amount' => '2000.00',
        'liquidity_limit' => '15000.00',
    ], $changes);
}

function requisiteCryptoPayload(array $changes = []): array
{
    return array_replace([
        'label' => 'Synthetic USDT address',
        'method' => 'usdt',
        'currency' => 'USDT',
        'network' => 'tron',
        // Public zero address for validation only, never a payment destination.
        'address' => 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb',
        'min_amount' => '1.000001',
        'max_amount' => '100.000001',
        'liquidity_limit' => null,
    ], $changes);
}

function createTestRequisite($test, ?User $owner = null, ?array $payload = null): PaymentRequisite
{
    $owner ??= $test->requisiteOwner;
    $test->actingAs($owner)->post(route('requisites.store'), $payload ?? requisiteCardPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));

    return PaymentRequisite::query()->where('user_id', $owner->id)->sole();
}

test('requisite routes require authentication and approved account access', function () {
    $this->get(route('requisites.index'))->assertRedirect(route('login'));
    $this->post(route('requisites.store'), requisiteCardPayload())->assertRedirect(route('login'));

    $unapproved = User::factory()->create(['approved_at' => null]);
    $this->actingAs($unapproved)->get(route('requisites.index'))->assertRedirect(route('dashboard'));
    $this->post(route('requisites.store'), requisiteCardPayload())->assertRedirect(route('dashboard'));
    $this->assertDatabaseCount('payment_requisites', 0);
});

test('approved owner can create list edit toggle and delete a requisite without moving payments', function () {
    $requisite = createTestRequisite($this);
    expect($requisite->is_active)->toBeFalse();

    $this->get(route('requisites.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('requisites/Index')
        ->has('requisites.data', 1)
        ->where('requisites.data.0.label', 'Synthetic test card')
        ->where('requisites.data.0.number_masked', '•••• 4242')
        ->missing('requisites.data.0.number'));

    $this->patch(route('requisites.update', $requisite), requisiteCardPayload([
        'label' => 'Edited synthetic card',
        'number' => '',
        'max_amount' => '3000.123456',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));

    expect($requisite->fresh()->label)->toBe('Edited synthetic card')
        ->and($requisite->fresh()->max_amount)->toBe('3000.123456')
        ->and($requisite->fresh()->number)->toBe('4242424242424242');

    $this->patch(route('requisites.update', $requisite), ['is_active' => true])->assertSessionHasNoErrors();
    expect($requisite->fresh()->is_active)->toBeTrue();
    $this->patch(route('requisites.update', $requisite), ['is_active' => false])->assertSessionHasNoErrors();
    expect($requisite->fresh()->is_active)->toBeFalse();

    $this->delete(route('requisites.destroy', $requisite))->assertRedirect(route('requisites.index'));
    $this->assertDatabaseMissing('payment_requisites', ['id' => $requisite->id]);
    expect(Address::count())->toBe(0)->and(Invoice::count())->toBe(0);
});

test('card number and holder are encrypted at rest and full number never appears in page or model JSON', function () {
    $requisite = createTestRequisite($this);
    $raw = DB::table('payment_requisites')->where('id', $requisite->id)->first();
    expect($raw->number)->not->toContain('4242424242424242')
        ->and($raw->holder)->not->toContain('SYNTHETIC TEST HOLDER')
        ->and($requisite->number)->toBe('4242424242424242')
        ->and($requisite->holder)->toBe('SYNTHETIC TEST HOLDER')
        ->and($requisite->toArray())->not->toHaveKeys(['number', 'holder']);

    $response = $this->get(route('requisites.index'))->assertOk()->assertDontSee('4242424242424242');
    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) $response->viewData('page')['version']])->get(route('requisites.index'))
        ->assertOk()->assertDontSee('4242424242424242');
});

test('requisite list and mutations stay scoped to their owner including admin viewers', function () {
    $own = createTestRequisite($this);
    $other = User::factory()->create(['approved_at' => now()]);
    $foreign = createTestRequisite($this, $other, requisiteCardPayload(['label' => 'Other owner secret label']));
    $this->requisiteOwner->addRole(Role::firstOrCreate(['name' => 'admin']));

    $this->actingAs($this->requisiteOwner)->get(route('requisites.index'))->assertOk()
        ->assertDontSee('Other owner secret label')
        ->assertInertia(fn (Assert $page) => $page->has('requisites.data', 1)->where('requisites.data.0.id', $own->id));
    $this->patchJson(route('requisites.update', $foreign), [])->assertNotFound();
    $this->patchJson(route('requisites.update', $foreign), ['is_active' => true])->assertNotFound();
    $this->deleteJson(route('requisites.destroy', $foreign))->assertNotFound();
    expect($foreign->fresh()->label)->toBe('Other owner secret label')
        ->and($foreign->fresh()->is_active)->toBeFalse();
});

test('request user id and last four digits cannot spoof requisite ownership or card metadata', function () {
    $other = User::factory()->create(['approved_at' => now()]);
    $requisite = createTestRequisite($this, payload: requisiteCardPayload([
        'user_id' => $other->id,
        'last4' => '9999',
    ]));
    expect($requisite->user_id)->toBe($this->requisiteOwner->id)->and($requisite->last4)->toBe('4242');

    $this->patch(route('requisites.update', $requisite), requisiteCardPayload([
        'user_id' => $other->id,
        'last4' => '9999',
        'number' => '',
    ]))->assertSessionHasNoErrors();
    expect($requisite->fresh()->user_id)->toBe($this->requisiteOwner->id)
        ->and($requisite->fresh()->last4)->toBe('4242');
});

test('method filtering runs before pagination and keeps owner scope on every page', function () {
    $wallet = createTestRequisite($this, payload: requisiteCryptoPayload());
    $this->post(route('requisites.store'), requisiteCardPayload())->assertSessionHasNoErrors();
    $card = PaymentRequisite::query()->where('user_id', $this->requisiteOwner->id)->where('method', 'card')->sole();
    for ($index = 1; $index <= 20; $index++) {
        $copy = $card->replicate();
        $copy->label = 'Synthetic paginated card '.$index;
        $copy->save();
    }
    $other = User::factory()->create(['approved_at' => now()]);
    createTestRequisite($this, $other, requisiteCryptoPayload(['label' => 'Foreign filtered wallet']));

    $this->actingAs($this->requisiteOwner)->get(route('requisites.index', ['method' => 'usdt']))
        ->assertOk()->assertDontSee('Foreign filtered wallet')->assertInertia(fn (Assert $page) => $page
        ->has('requisites.data', 1)
        ->where('requisites.total', 1)
        ->where('requisites.data.0.id', $wallet->id)
        ->where('requisites.data.0.method', 'usdt'));

    $this->get(route('requisites.index', ['method' => 'card']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('requisites.data', 20)
            ->where('requisites.total', 21)
            ->where('requisites.next_page_url', fn ($url) => is_string($url) && str_contains($url, 'method=card'))
            ->where('requisites.data', fn ($items) => collect($items)->every(fn ($item) => $item['method'] === 'card')));

    $this->get(route('requisites.index', ['method' => 'card', 'page' => 2]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('requisites.data', 1)
            ->where('requisites.current_page', 2)
            ->where('requisites.data.0.id', $card->id));
});

test('method filter rejects unsupported values instead of broadening the list', function (mixed $method) {
    $this->actingAs($this->requisiteOwner)->getJson(route('requisites.index', ['method' => $method]))
        ->assertUnprocessable()->assertJsonValidationErrors('method');
})->with([
    'unsupported method' => ['wire'],
    'array query value' => [['card']],
]);

test('card requisites reject missing or invalid financial details', function (array $changes, string $error) {
    $this->actingAs($this->requisiteOwner)->postJson(route('requisites.store'), requisiteCardPayload($changes))
        ->assertUnprocessable()->assertJsonValidationErrors($error);
    $this->assertDatabaseCount('payment_requisites', 0);
})->with([
    'bank required' => [['bank' => null], 'bank'],
    'holder required' => [['holder' => ''], 'holder'],
    'number required' => [['number' => ''], 'number'],
    'Luhn checksum' => [['number' => '4242424242424241'], 'number'],
    'zero-only card number' => [['number' => '0000000000000000'], 'number'],
    'unsupported bank' => [['bank' => 'unknown-bank'], 'bank'],
    'other bank name required' => [['bank' => 'other', 'bank_name' => ''], 'bank_name'],
    'unsupported method' => [['method' => 'wire'], 'method'],
    'card cannot use USDT' => [['currency' => 'USDT'], 'currency'],
    'minimum must be positive' => [['min_amount' => '0'], 'min_amount'],
    'maximum cannot be lower' => [['min_amount' => '200', 'max_amount' => '100'], 'max_amount'],
    'liquidity cannot be negative' => [['liquidity_limit' => '-1'], 'liquidity_limit'],
    'amounts cannot use floating point JSON' => [['min_amount' => 100.1], 'min_amount'],
    'scientific notation not accepted' => [['min_amount' => '1e2'], 'min_amount'],
    'subprecision amount not accepted' => [['min_amount' => '0.0000001'], 'min_amount'],
    'database precision overflow rejected' => [['max_amount' => '1000000000000.000000'], 'max_amount'],
    'repeated decimal commas rejected' => [['min_amount' => '1,234,56'], 'min_amount'],
    'mixed decimal separators rejected' => [['min_amount' => '1.234,56'], 'min_amount'],
    'minimum cannot be whitespace only' => [['min_amount' => "\u{00a0}\u{202f}"], 'min_amount'],
    'maximum cannot be whitespace only' => [['max_amount' => "\u{00a0}\u{202f}"], 'max_amount'],
]);

test('browser validation does not flash sensitive card fields into session', function () {
    $this->actingAs($this->requisiteOwner)->from(route('requisites.index'))
        ->post(route('requisites.store'), requisiteCardPayload(['max_amount' => '1']))
        ->assertRedirect(route('requisites.index'))->assertSessionHasErrors('max_amount')
        ->assertSessionMissing('_old_input.number')->assertSessionMissing('_old_input.holder');
});

test('other-bank cards store bank details and ignore unrelated wallet fields', function () {
    $requisite = createTestRequisite($this, payload: requisiteCardPayload([
        'bank' => 'other',
        'bank_name' => 'Synthetic bank',
        'network' => 'tron',
        'address' => 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb',
    ]));
    expect($requisite->bank_name)->toBe('Synthetic bank')
        ->and($requisite->address)->toBeNull()->and($requisite->network)->toBeNull();
});

test('USDT requisites validate supported networks without creating monitored payment addresses', function (string $network, string $address) {
    $requisite = createTestRequisite($this, payload: requisiteCryptoPayload([
        'network' => $network,
        'address' => $address,
        'bank' => 'kaspi',
        'holder' => 'SYNTHETIC TEST HOLDER',
        'number' => '4242424242424242',
    ]));
    expect($requisite->method)->toBe('usdt')->and($requisite->currency)->toBe('USDT')
        ->and($requisite->network)->toBe($network)->and($requisite->address)->toBe($address)
        ->and($requisite->number)->toBeNull()->and($requisite->holder)->toBeNull()
        ->and($requisite->bank)->toBeNull()->and($requisite->last4)->toBeNull()
        ->and($requisite->is_active)->toBeFalse()
        ->and(Address::count())->toBe(0)->and(Invoice::count())->toBe(0);
})->with([
    'TRON checksum address' => ['tron', 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb'],
    'Ethereum address' => ['ethereum', '0x1111111111111111111111111111111111111111'],
    'BSC address' => ['bsc', '0x1111111111111111111111111111111111111111'],
]);

test('USDT requisites reject invalid currency network or address', function (array $changes, string $error) {
    $this->actingAs($this->requisiteOwner)->postJson(route('requisites.store'), requisiteCryptoPayload($changes))
        ->assertUnprocessable()->assertJsonValidationErrors($error);
    $this->assertDatabaseCount('payment_requisites', 0);
})->with([
    'fiat currency' => [['currency' => 'USD'], 'currency'],
    'unknown network' => [['network' => 'solana'], 'network'],
    'missing network' => [['network' => null], 'network'],
    'missing address' => [['address' => ''], 'address'],
    'invalid TRON checksum' => [['address' => 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwa'], 'address'],
    'TRON case cannot be changed' => [['address' => 't9yd14nj9j7xab4dbgeix9h8unkkhxuwwb'], 'address'],
    'short Ethereum address' => [['network' => 'ethereum', 'address' => '0x1111'], 'address'],
    'nonhex BSC address' => [['network' => 'bsc', 'address' => '0xgggggggggggggggggggggggggggggggggggggggg'], 'address'],
]);

test('unchanged card credentials survive a patch with number omitted', function () {
    $requisite = createTestRequisite($this);
    $payload = requisiteCardPayload(['label' => 'Changed label']);
    unset($payload['number']);
    $this->patch(route('requisites.update', $requisite), $payload)->assertSessionHasNoErrors();
    expect($requisite->fresh()->number)->toBe('4242424242424242')
        ->and($requisite->fresh()->last4)->toBe('4242');
});

test('decimal limits retain all six places and allow a zero liquidity cap', function () {
    $requisite = createTestRequisite($this, payload: requisiteCardPayload([
        'min_amount' => '0.000001',
        'max_amount' => '999999999999.999999',
        'liquidity_limit' => '0',
    ]));
    expect($requisite->min_amount)->toBe('0.000001')
        ->and($requisite->max_amount)->toBe('999999999999.999999')
        ->and($requisite->liquidity_limit)->toBe('0.000000');
});

test('formatted amount strings are normalized precisely on create and edit', function (string $separator) {
    $requisite = createTestRequisite($this, payload: requisiteCardPayload([
        'min_amount' => '1'.$separator.'000,25',
        'max_amount' => '3'.$separator.'000,123456',
        'liquidity_limit' => '9'.$separator.'000,50',
    ]));
    expect($requisite->min_amount)->toBe('1000.250000')
        ->and($requisite->max_amount)->toBe('3000.123456')
        ->and($requisite->liquidity_limit)->toBe('9000.500000');

    $this->patch(route('requisites.update', $requisite), requisiteCardPayload([
        'number' => '',
        'min_amount' => '2'.$separator.'000,50',
        'max_amount' => '4'.$separator.'000,654321',
        'liquidity_limit' => '0,00',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));
    expect($requisite->fresh()->min_amount)->toBe('2000.500000')
        ->and($requisite->fresh()->max_amount)->toBe('4000.654321')
        ->and($requisite->fresh()->liquidity_limit)->toBe('0.000000');
})->with([
    'regular spaces' => [' '],
    'nonbreaking spaces' => ["\u{00a0}"],
    'narrow nonbreaking spaces' => ["\u{202f}"],
]);

test('invalid edits leave existing encrypted credentials and limits unchanged', function () {
    $requisite = createTestRequisite($this);
    $original = $requisite->getRawOriginal();
    $this->patchJson(route('requisites.update', $requisite), requisiteCardPayload([
        'label' => 'Should not be saved',
        'holder' => 'INVALID EDIT HOLDER',
        'number' => '4242424242424241',
        'max_amount' => '1',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['number', 'max_amount']);
    expect($requisite->fresh()->getRawOriginal())->toBe($original);
});

test('whitespace-only optional liquidity clears the stored cap', function () {
    $requisite = createTestRequisite($this);
    $this->patch(route('requisites.update', $requisite), requisiteCardPayload([
        'number' => '',
        'liquidity_limit' => "\u{00a0}\u{202f}",
    ]))->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));
    expect($requisite->fresh()->liquidity_limit)->toBeNull();
});

test('changing methods requires new credentials and removes old sensitive fields', function () {
    $requisite = createTestRequisite($this);
    $this->patchJson(route('requisites.update', $requisite), requisiteCryptoPayload(['address' => '']))
        ->assertUnprocessable()->assertJsonValidationErrors('address');
    expect($requisite->fresh()->method)->toBe('card');

    $this->patch(route('requisites.update', $requisite), requisiteCryptoPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));
    $wallet = $requisite->fresh();
    expect($wallet->method)->toBe('usdt')->and($wallet->number)->toBeNull()
        ->and($wallet->holder)->toBeNull()->and($wallet->bank)->toBeNull()->and($wallet->last4)->toBeNull();
    $raw = DB::table('payment_requisites')->where('id', $requisite->id)->first();
    expect($raw->number)->toBeNull()->and($raw->holder)->toBeNull();

    $this->patchJson(route('requisites.update', $requisite), requisiteCardPayload(['number' => '']))
        ->assertUnprocessable()->assertJsonValidationErrors('number');
    expect($requisite->fresh()->method)->toBe('usdt');

    $this->patch(route('requisites.update', $requisite), requisiteCardPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('requisites.index'));
    expect($requisite->fresh()->method)->toBe('card')
        ->and($requisite->fresh()->number)->toBe('4242424242424242')
        ->and($requisite->fresh()->address)->toBeNull()->and($requisite->fresh()->network)->toBeNull();
});
