<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Contracts\Client\ClientServiceContract;
use App\Exceptions\Client\ClientException;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class ClientService implements ClientServiceContract
{
    public function create(User $user, string $externalId, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client
    {
        $normalizedExternalId = $this->normalizeExternalId($externalId);

        if ($normalizedExternalId === '') {
            throw new ClientException(__('messages.clients.errors.empty_external_id'));
        }

        if ($this->exists($user, $normalizedExternalId)) {
            throw new ClientException(__('messages.clients.errors.duplicate_external_id'));
        }

        return $this->store($user, $normalizedExternalId, $name, $telegram, $contact);
    }

    public function update(Client $client, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client
    {
        $client->name = $this->sanitize($name);
        $client->telegram = $this->sanitize($telegram);
        $client->contact = $this->sanitize($contact);
        $client->save();

        return $client;
    }

    public function findOrCreate(User $user, string $externalId, ?string $name = null, ?string $telegram = null, ?string $contact = null): Client
    {
        $normalizedExternalId = $this->normalizeExternalId($externalId);

        if ($normalizedExternalId === '') {
            throw new ClientException(__('messages.clients.errors.empty_external_id'));
        }

        $existing = $this->findByExternalId($user, $normalizedExternalId);
        if ($existing !== null) {
            $updated = $this->mergeOptionalFields($existing, $name, $telegram, $contact);
            return $updated;
        }

        return $this->store($user, $normalizedExternalId, $name, $telegram, $contact);
    }

    private function store(User $user, string $externalId, ?string $name, ?string $telegram, ?string $contact): Client
    {
        try {
            return Client::query()->create([
                'user_id' => $user->id,
                'external_id' => $externalId,
                'name' => $this->sanitize($name),
                'telegram' => $this->sanitize($telegram),
                'contact' => $this->sanitize($contact),
            ]);
        } catch (QueryException $exception) {
            throw new ClientException(__('messages.clients.errors.duplicate_external_id'), (int) $exception->getCode(), $exception);
        }
    }

    private function normalizeExternalId(string $externalId): string
    {
        $trimmed = trim($externalId);
        return Str::limit($trimmed, 128, '');
    }

    private function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function exists(User $user, string $externalId): bool
    {
        return Client::query()
            ->where('user_id', $user->id)
            ->where('external_id', $externalId)
            ->exists();
    }

    private function findByExternalId(User $user, string $externalId): ?Client
    {
        return Client::query()
            ->where('user_id', $user->id)
            ->where('external_id', $externalId)
            ->first();
    }

    private function mergeOptionalFields(Client $client, ?string $name, ?string $telegram, ?string $contact): Client
    {
        $changed = false;

        $nameSanitized = $this->sanitize($name);
        if ($nameSanitized !== null && $client->name !== $nameSanitized) {
            $client->name = $nameSanitized;
            $changed = true;
        }

        $telegramSanitized = $this->sanitize($telegram);
        if ($telegramSanitized !== null && $client->telegram !== $telegramSanitized) {
            $client->telegram = $telegramSanitized;
            $changed = true;
        }

        $contactSanitized = $this->sanitize($contact);
        if ($contactSanitized !== null && $client->contact !== $contactSanitized) {
            $client->contact = $contactSanitized;
            $changed = true;
        }

        if ($changed) {
            $client->save();
        }

        return $client;
    }
}


