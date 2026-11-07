<?php

declare(strict_types=1);

namespace App\Contracts\Money;

use App\Enums\Currency;
use App\Services\Money\MoneyAmount;
use Brick\Math\BigDecimal;

/**
 * Контракт сервиса для работы с денежными величинами.
 */
interface MoneyServiceContract
{
    /** Создать сумму из строкового/числового значения с учётом валюты. */
    public function create(string|int|float|BigDecimal $amount, Currency $currency): MoneyAmount;

    /** Распарсить из строки (например, "1_234.567"). */
    public function parse(string $value, Currency $currency): MoneyAmount;

    /** Создать из минорных единиц (например, центы/саны). */
    public function fromMinor(int|string $minorUnits, Currency $currency): MoneyAmount;

    /** Получить минорные единицы как строку без потери точности. */
    public function toMinor(MoneyAmount $money): string;

    /** Сложение, валюта должна совпадать. */
    public function add(MoneyAmount $a, MoneyAmount $b): MoneyAmount;

    /** Вычитание, валюта должна совпадать. */
    public function subtract(MoneyAmount $a, MoneyAmount $b): MoneyAmount;

    /** Умножение на коэффициент. */
    public function multiply(MoneyAmount $a, string|int|float|BigDecimal $multiplier): MoneyAmount;

    /** Сравнение: -1 <, 0 =, 1 > (по значению, при совпадении валюты). */
    public function compare(MoneyAmount $a, MoneyAmount $b): int;

    /** Форматирование в строку для отображения. */
    public function format(MoneyAmount $money, bool $trimTrailingZeros = true): string;
}


