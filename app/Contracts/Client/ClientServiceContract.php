<?php

declare(strict_types=1);

namespace App\Contracts\Client;

use App\Models\Client;
use App\Models\User;

interface ClientServiceContract
{
    public function create(User $user, string $externalId, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client;

    public function update(Client $client, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client;

    public function findOrCreate(User $user, string $externalId, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client;
}


