<?php

declare(strict_types=1);

namespace App\Services\Blockchain;

use Brick\Math\BigInteger;
use InvalidArgumentException;

final class TronAddress
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public static function hex(string $address): string
    {
        if (! preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/D', $address)) {
            throw new InvalidArgumentException('Invalid TRON Base58 address.');
        }
        $number = BigInteger::zero();
        foreach (str_split($address) as $character) {
            $number = $number->multipliedBy(58)->plus(strpos(self::ALPHABET, $character));
        }
        $hex = str_pad($number->toBase(16), 50, '0', STR_PAD_LEFT);
        $payload = substr($hex, 0, 42);
        $checksum = substr(hash('sha256', hash('sha256', hex2bin($payload), true)), 0, 8);
        if (strlen($hex) !== 50 || ! str_starts_with($payload, '41') || ! hash_equals($checksum, substr($hex, 42))) {
            throw new InvalidArgumentException('Invalid TRON address checksum.');
        }

        return substr($payload, 2);
    }
}
