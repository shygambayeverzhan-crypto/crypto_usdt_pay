<?php

declare(strict_types=1);

namespace App\Contracts\Invoice;

use App\Models\Invoice;

interface InvoiceCallbackServiceContract
{
    /**
     * Отправляет POST callback для указанного инвойса, если задан `callback_url`.
     *
     * @param Invoice $invoice
     * @param string $event Например: created, status_changed
     */
    public function send(Invoice $invoice, string $event): void;
}


