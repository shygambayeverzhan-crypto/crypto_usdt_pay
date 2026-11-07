<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Enums\Currency;
use Brick\Math\BigDecimal;

/**
 * Иммутабельное значение денежной суммы.
 */
final class MoneyAmount
{
    public function __construct(
        public readonly BigDecimal $amount,
        public readonly Currency $currency,
    ) {
    }
}


