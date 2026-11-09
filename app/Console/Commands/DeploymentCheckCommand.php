<?php

namespace App\Console\Commands;

use App\Support\DeploymentReadiness;
use Illuminate\Console\Command;

final class DeploymentCheckCommand extends Command
{
    protected $signature = 'deployment:check';

    protected $description = 'Read-only check of application key, database connection and pending migrations';

    public function handle(DeploymentReadiness $readiness): int
    {
        $errors = $readiness->errors();
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors) {
            return self::FAILURE;
        }
        $this->info('Panel database is ready. Payment processing requires separate acceptance.');

        return self::SUCCESS;
    }
}
