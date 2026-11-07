<?php

declare(strict_types=1);

namespace App\Contracts\IpGeolocation;

use App\Services\IpGeolocation\IpGeolocationResult;

interface IpGeolocationServiceContract
{
    public function lookup(string $ip): IpGeolocationResult;
}

