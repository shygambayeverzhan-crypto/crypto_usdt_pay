<?php

declare(strict_types=1);

namespace App\Exceptions\Blockchain;

final class ApiRequestException extends BlockchainException
{
    public static function forHttpStatus(int $status, string $url, string $body = ''): self
    {
        $message = "TronGrid request failed ({$status}) for {$url}";
        if ($body !== '') {
            $message .= ": {$body}";
        }
        return new self($message);
    }
}


