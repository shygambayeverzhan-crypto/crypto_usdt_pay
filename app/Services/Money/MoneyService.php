<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Contracts\Money\MoneyServiceContract;
use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Сервис для безопасной работы с денежными суммами на основе BigDecimal.
 */
final class MoneyService implements MoneyServiceContract
{
    /**
     * Количество знаков после запятой для каждой валюты.
     * Если валюты нет в списке — применяется значение по умолчанию.
     */
    private const DEFAULT_SCALE = 6;

    /** @var array<string,int> */
    private array $currencyScale = [
        // Tron-based: TRX, USDT(TRC20) — 6 знаков
        'TRX' => 6,
        'USDT' => 6,
    ];

    public function create(string|int|float|BigDecimal $amount, Currency $currency): MoneyAmount
    {
        $decimal = $this->normalizeToDecimal($amount, $this->scaleFor($currency));
        return new MoneyAmount($decimal, $currency);
    }

    public function parse(string $value, Currency $currency): MoneyAmount
    {
        $normalized = str_replace([' ', '_', ','], ['', '', '.'], trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException(__('messages.money.empty_string'));
        }
        $scale = $this->scaleFor($currency);
        $decimal = BigDecimal::of($normalized)->toScale($scale, RoundingMode::DOWN);
        return new MoneyAmount($decimal, $currency);
    }

    public function fromMinor(int|string $minorUnits, Currency $currency): MoneyAmount
    {
        $scale = $this->scaleFor($currency);
        $minor = BigDecimal::of($minorUnits);
        $factor = BigDecimal::one()->withPointMovedLeft($scale);
        return new MoneyAmount($minor->multipliedBy($factor), $currency);
    }

    public function toMinor(MoneyAmount $money): string
    {
        $scale = $this->scaleFor($money->currency);
        return $money->amount
            ->withPointMovedRight($scale)
            ->toScale(0, RoundingMode::DOWN)
            ->__toString();
    }

    public function add(MoneyAmount $a, MoneyAmount $b): MoneyAmount
    {
        $this->assertSameCurrency($a, $b);
        return new MoneyAmount($a->amount->plus($b->amount), $a->currency);
    }

    public function subtract(MoneyAmount $a, MoneyAmount $b): MoneyAmount
    {
        $this->assertSameCurrency($a, $b);
        return new MoneyAmount($a->amount->minus($b->amount), $a->currency);
    }

    public function multiply(MoneyAmount $a, string|int|float|BigDecimal $multiplier): MoneyAmount
    {
        $scale = $this->scaleFor($a->currency);
        $m = $this->normalizeToDecimal($multiplier, $scale);
        $result = $a->amount->multipliedBy($m)->toScale($scale, RoundingMode::DOWN);
        return new MoneyAmount($result, $a->currency);
    }

    public function compare(MoneyAmount $a, MoneyAmount $b): int
    {
        $this->assertSameCurrency($a, $b);
        return $a->amount->compareTo($b->amount);
    }

    public function format(MoneyAmount $money, bool $trimTrailingZeros = true): string
    {
        $scale = $this->scaleFor($money->currency);
        $scaled = $money->amount->toScale($scale, RoundingMode::DOWN)->__toString();
        if ($trimTrailingZeros && str_contains($scaled, '.')) {
            $scaled = rtrim(rtrim($scaled, '0'), '.');
        }
        return $scaled;
    }

    private function assertSameCurrency(MoneyAmount $a, MoneyAmount $b): void
    {
        if ($a->currency !== $b->currency) {
            throw new InvalidArgumentException(__('messages.money.currency_mismatch'));
        }
    }

    private function scaleFor(Currency $currency): int
    {
        return $this->currencyScale[$currency->value] ?? self::DEFAULT_SCALE;
    }

    private function normalizeToDecimal(string|int|float|BigDecimal $value, int $scale): BigDecimal
    {
        $decimal = $value instanceof BigDecimal ? $value : BigDecimal::of($value);
        return $decimal->toScale($scale, RoundingMode::DOWN);
    }
}


