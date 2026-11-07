<?php

namespace App\Services\Blockchain;

final class RequestBudget
{
    private ?float $deadline = null;

    public function start(int $seconds): void
    {
        $this->deadline = microtime(true) + $seconds;
    }

    public function clear(): void
    {
        $this->deadline = null;
    }

    public function remaining(): float
    {
        return $this->deadline === null ? 3600 : max(0, $this->deadline - microtime(true));
    }

    public function timeout(): float
    {
        if ($this->remaining() < 1) {
            throw new BudgetExhausted('Payment batch deadline reached; retry on the next tick.');
        }

        return min(15, $this->remaining());
    }
}
