<?php

declare(strict_types=1);

namespace App\Services\IpGeolocation;

final class IpGeolocationResult
{
    public const STATUS_OK = 'ok';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_LIMITED = 'limited';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $ip,
        public readonly ?array $payload = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'ip' => $this->ip,
            'payload' => $this->payload,
        ];
    }
}

