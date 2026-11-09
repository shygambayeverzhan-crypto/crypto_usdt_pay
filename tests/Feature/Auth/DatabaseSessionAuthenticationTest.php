<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();

    config([
        'cache.default' => 'database',
        'session.driver' => 'database',
        'laratrust.cache.enabled' => true,
        'services.ipgeolocation.api_key' => null,
    ]);

    // Providers register named limiters before tests can select a cache store.
    // Keep their real callbacks while exercising the production database store.
    $databaseLimiter = new CacheRateLimiter(Cache::store('database'));
    foreach (['login', 'two-factor', 'api.v1'] as $name) {
        $callback = RateLimiter::limiter($name);
        if ($callback !== null) {
            $databaseLimiter->for($name, $callback);
        }
    }
    RateLimiter::swap($databaseLimiter);
});

test('owner can reset a password and enter the dashboard with database sessions and cache', function () {
    $owner = User::factory()->withoutTwoFactor()->create(['approved_at' => now()]);
    $owner->addRole(Role::query()->firstOrCreate(['name' => 'admin']));
    $password = 'test-owner-password-123';

    $this->post(route('password.update'), [
        'token' => Password::createToken($owner),
        'email' => $owner->email,
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => $password,
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($owner);
    $this->assertDatabaseHas('sessions', ['user_id' => $owner->id]);
    $this->assertDatabaseHas('user_login_histories', ['user_id' => $owner->id]);

    $this->app['auth']->forgetGuards();
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('auth.is_admin', true)
        ->where('stats.totalInvoices', 0));

    expect(DB::table('cache')->count())->toBeGreaterThan(0);
});

test('database login throttling rejects repeated failures and expires normally', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-test-password',
        ])->assertSessionHasErrors('email')->assertRedirect();
        $this->assertGuest();
    }

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertTooManyRequests();
    $this->assertGuest();
    $this->assertDatabaseMissing('user_login_histories', ['user_id' => $user->id]);
    expect(DB::table('cache')->count())->toBeGreaterThan(0);

    $this->get(route('login'))->assertOk();
    $this->travel(61)->seconds();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
});

test('Vercel PostgreSQL uses unnamed parameter binding for pooler compatible queries', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This driver regression requires PostgreSQL.');
    }

    $environment = Env::getRepository();
    $originalRuntime = $environment->get('VERCEL_RUNTIME');
    $originalDisablePrepares = $environment->get('DB_DISABLE_PREPARES');
    try {
        $environment->set('VERCEL_RUNTIME', 'true');
        $environment->clear('DB_DISABLE_PREPARES');
        $databaseConfig = require config_path('database.php');
    } finally {
        $originalRuntime === null
            ? $environment->clear('VERCEL_RUNTIME')
            : $environment->set('VERCEL_RUNTIME', $originalRuntime);
        $originalDisablePrepares === null
            ? $environment->clear('DB_DISABLE_PREPARES')
            : $environment->set('DB_DISABLE_PREPARES', $originalDisablePrepares);
    }

    expect($databaseConfig['connections']['pgsql']['options'][PDO::PGSQL_ATTR_DISABLE_PREPARES])->toBeTrue();

    // Use the local test connection credentials, never an environment URL.
    $connectionConfig = DB::connection()->getConfig();
    $connectionConfig['url'] = null;
    $connectionConfig['options'] = $databaseConfig['connections']['pgsql']['options'];
    config(['database.connections.unnamed_parameters_test' => $connectionConfig]);

    try {
        $connection = DB::connection('unnamed_parameters_test');
        expect($connection->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES))->toBeFalse();
        $literal = "test'); DROP TABLE users; --";
        $result = $connection->selectOne('select cast(? as text) as literal, cast(? as boolean) as flag, cast(? as bigint) as amount', [$literal, false, 123456789]);
        expect($result->literal)->toBe($literal)
            ->and($result->flag)->toBeFalse()
            ->and((int) $result->amount)->toBe(123456789);
    } finally {
        DB::purge('unnamed_parameters_test');
    }
});
