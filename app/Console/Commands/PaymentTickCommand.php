<?php

namespace App\Console\Commands;

use App\Services\Invoice\PaymentBatch;
use Illuminate\Console\Command;

final class PaymentTickCommand extends Command
{
    protected $signature = 'payments:tick';

    protected $description = 'Bounded payment reconciliation and notification retry';

    public function handle(PaymentBatch $batch): int
    {
        $result = $batch->run();
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return $result['failed'] ? self::FAILURE : self::SUCCESS;
    }
}
