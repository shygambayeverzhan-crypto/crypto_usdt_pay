<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\AssignDefaultUserRole;
use App\Listeners\RecordUserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            AssignDefaultUserRole::class,
        ],
        Login::class => [
            RecordUserLogin::class,
        ],
    ];
}


