<?php

declare(strict_types=1);

namespace App\Exceptions\Blockchain;

final class TokenContractNotFoundException extends BlockchainException
{
    public static function forAddress(string $address, string $contract): self
    {
        return new self("Token contract {$contract} not found for address {$address}");
    }
}


