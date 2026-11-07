<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Планировщик: обновление балансов адресов каждую минуту
Schedule::command('addresses:sync-balances --only-active=1')->everyMinute();

// Планировщик: создание инвойса для callback sandbox каждые 5 минут
//Schedule::command('invoices:callback-sandbox')->everyFiveMinutes();
