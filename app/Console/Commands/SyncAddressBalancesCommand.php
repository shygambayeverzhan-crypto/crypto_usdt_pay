<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\UpdateAddressBalanceJob;
use App\Models\Address;
use Illuminate\Console\Command;

class SyncAddressBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'addresses:sync-balances {--only-active=1 : Обновлять только активные адреса}';

    /**
     * The console command description.
     */
    protected $description = 'Создать задания на обновление балансов для всех адресов';

    public function handle(): int
    {
        $onlyActive = (bool) (int) $this->option('only-active');

        $query = Address::query()->select(['id']);
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $count = 0;
        $query->chunkById(100, function ($chunk) use (&$count) {
            foreach ($chunk as $row) {
                UpdateAddressBalanceJob::dispatch((string) $row->id);
                $count++;
            }
        });

        $this->info("Создано заданий: {$count}");
        return self::SUCCESS;
    }
}


