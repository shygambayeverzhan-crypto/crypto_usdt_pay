<?php

use App\Support\DeploymentReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->originalDatabase = config('database.default');
});

afterEach(function () {
    config(['database.default' => $this->originalDatabase]);
});

test('readiness succeeds with migrated database while payments remain disabled', function () {
    config(['vercel.enabled' => false, 'vercel.payments_enabled' => false]);
    $this->getJson('/ready')->assertOk()->assertExactJson(['status' => 'ready'])
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->artisan('deployment:check')->assertSuccessful();
    expect(config('vercel.payments_enabled'))->toBeFalse();
});

test('missing database configuration fails readiness but not liveness', function () {
    config(['vercel.enabled' => true, 'database.default' => 'pgsql',
        'database.connections.pgsql' => ['url' => null, 'host' => '', 'database' => '', 'username' => '', 'password' => '']]);
    $this->get('/up')->assertOk();
    $this->getJson('/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
    $this->get('/login')->assertStatus(503)->assertSee('Панель ещё настраивается');
    $this->artisan('deployment:check')->expectsOutputToContain('DATABASE_NOT_CONFIGURED')->assertFailed();
});

test('malformed key fails before cookie encryption or database access', function () {
    config(['vercel.enabled' => true, 'app.key' => 'base64:invalid']);
    $this->get('/login')->assertStatus(503);
    expect(app(DeploymentReadiness::class)->configurationErrors())->toContain('APP_KEY_INVALID');
});

test('pending migrations fail readiness without running them', function () {
    config(['vercel.enabled' => false]);
    $migration = DB::table('migrations')->orderByDesc('id')->first();
    DB::table('migrations')->where('id', $migration->id)->delete();
    $this->getJson('/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
    expect(app(DeploymentReadiness::class)->errors())->toBe(['MIGRATIONS_REQUIRED']);
    expect(DB::table('migrations')->where('migration', $migration->migration)->exists())->toBeFalse();
});

test('connection failures do not disclose database credentials', function () {
    config(['vercel.enabled' => false]);
    $connection = Mockery::mock(\Illuminate\Database\Connection::class);
    $connection->shouldReceive('select')->with('select 1')->andThrow(new RuntimeException('postgres://user:private-password@private-host/db'));
    $manager = DB::getFacadeRoot();
    try {
        DB::shouldReceive('connection')->andReturn($connection);
        $this->getJson('/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable'])
            ->assertDontSee('private-password')->assertDontSee('private-host');
    } finally {
        DB::swap($manager);
    }
});
