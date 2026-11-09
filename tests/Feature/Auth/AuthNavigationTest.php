<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('invalid login returns to its form after Inertia navigation without a referrer', function () {
    $version = $this->get(route('home'))->assertOk()->viewData('page')['version'];
    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) $version,
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html',
    ])->get(route('login'))->assertOk();

    $email = 'unregistered-seller@example.test';
    $this->post(route('login.store'), [
        'email' => $email,
        'password' => 'invalid-test-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('email')
        ->assertSessionHas('_old_input.email', $email)
        ->assertSessionMissing('_old_input.password');
    $this->assertGuest();

    $this->get(route('login'))->assertOk()
        ->assertJsonPath('component', 'auth/Login')
        ->assertJsonPath('props.auth.user', null)
        ->assertJsonPath('props.errors.email', fn ($error) => is_string($error) && $error !== '');
});

test('missing login fields return validation errors to the login form', function () {
    $this->from(route('home'))->post(route('login.store'), [])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

test('invalid registration returns to its form after Inertia navigation without a referrer', function () {
    $version = $this->get(route('home'))->assertOk()->viewData('page')['version'];
    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) $version,
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html',
    ])->get(route('register'))->assertOk();

    $this->post(route('register.store'), [
        'name' => 'New Seller',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertRedirect(route('register'))
        ->assertSessionHasErrors(['email', 'password'])
        ->assertSessionHas('_old_input.name', 'New Seller')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);

    $this->get(route('register'))->assertOk()
        ->assertJsonPath('component', 'auth/Register')
        ->assertJsonPath('props.errors.email', fn ($error) => is_string($error) && $error !== '')
        ->assertJsonPath('props.errors.password', fn ($error) => is_string($error) && $error !== '');
});

test('JSON authentication validation keeps its 422 responses', function () {
    $this->postJson(route('login.store'), [
        'email' => 'unknown@example.test',
        'password' => 'invalid-test-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->postJson(route('register.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

test('authenticated visitors to the public home are directed to their dashboard', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user)->get(route('home'))->assertRedirect(route('dashboard'));
});

test('successful login preserves an intended protected destination', function () {
    $user = User::factory()->withoutTwoFactor()->create(['approved_at' => now()]);

    $this->withSession(['url.intended' => route('notifications.index')])->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('notifications.index'));
    $this->assertAuthenticatedAs($user);
});

test('public registration creates an unapproved seller without administrator privileges', function () {
    $this->post(route('register.store'), [
        'name' => 'New Seller',
        'email' => 'new-seller@example.test',
        'password' => 'test-seller-password-123',
        'password_confirmation' => 'test-seller-password-123',
        'role' => 'admin',
        'roles' => ['admin'],
        'approved_at' => now()->toDateTimeString(),
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'new-seller@example.test')->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->approved_at)->toBeNull()
        ->and($user->hasRole('admin'))->toBeFalse();

    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('auth.is_admin', false)
        ->where('auth.is_approved', false));
    $this->post(route('invoices.store'), [])->assertRedirect(route('dashboard'))->assertSessionHas('error');
    $this->get(route('admin.users.index'))->assertForbidden();
    $this->assertDatabaseCount('invoices', 0);

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'test-seller-password-123',
    ])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    $this->app['auth']->forgetGuards();
    $this->get(route('home'))->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('auth.user.id', $user->id)
        ->where('auth.is_admin', false)
        ->where('auth.is_approved', false));
});
