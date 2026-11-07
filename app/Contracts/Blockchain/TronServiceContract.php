<?php

declare(strict_types=1);

namespace App\Contracts\Blockchain;

interface TronServiceContract
{
    /**
     * Получить входящие переводы USDT (TRC20) на указанный адрес.
     * Возвращает массив нормализованных транзакций: [
     *   'txid' => string,
     *   'amount' => string, // в человекочитаемом виде (6 знаков после запятой)
     *   'timestamp' => int|null,
     * ]
     */
    public function getUsdtIncomingTransfers(string $address, int $limit = 30, ?string $contractAddress = null): array;

    /**
     * Получить исходящие переводы USDT (TRC20) с указанного адреса.
     * Формат возвращаемых элементов тот же, что и у входящих.
     */
    public function getUsdtOutgoingTransfers(string $address, int $limit = 30, ?string $contractAddress = null): array;
}


