<?php

use Telegram\Bot\Laravel\Facades\Telegram;

beforeEach(function () {
    Telegram::swap(Mockery::mock());
});

it('rejects a webhook without the shared secret', function () {
    config(['telegram.bots.mybot.secret_token' => str_repeat('a', 40)]);
    Telegram::shouldReceive('commandsHandler')->never();
    $this->postJson('/telegram/webhook', [])->assertForbidden();
});

it('allows Telegram to retry when command processing fails', function () {
    config(['telegram.bots.mybot.secret_token' => str_repeat('a', 40)]);
    Telegram::shouldReceive('commandsHandler')->once()->with(true)->andThrow(new RuntimeException('Unavailable'));
    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', str_repeat('a', 40))
        ->postJson('/telegram/webhook', [])->assertStatus(503);
});

it('refuses webhook setup with an insecure URL or missing secret', function () {
    config(['telegram.bots.mybot.token' => 'fixture', 'telegram.bots.mybot.webhook_url' => 'http://example.com/telegram/webhook']);
    Telegram::shouldReceive('bot')->never();
    $this->artisan('telegram:set-webhook')->assertFailed();
});

it('does not replace a webhook when the token belongs to a different bot', function () {
    config(['telegram.bots.mybot.token' => 'fixture', 'telegram.bots.mybot.username' => 'expected_bot',
        'telegram.bots.mybot.webhook_url' => 'https://example.com/telegram/webhook',
        'telegram.bots.mybot.secret_token' => str_repeat('x', 40)]);
    $bot = Mockery::mock();
    $bot->shouldReceive('setTimeOut')->once()->with(15);
    $bot->shouldReceive('getMe')->once()->andReturn((object) ['username' => 'another_bot']);
    $bot->shouldNotReceive('setWebhook');
    Telegram::shouldReceive('bot')->once()->with('mybot')->andReturn($bot);
    $this->artisan('telegram:set-webhook')->assertFailed();
});
