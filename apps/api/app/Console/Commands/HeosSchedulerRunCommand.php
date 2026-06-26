<?php

namespace App\Console\Commands;

use App\Services\Enterprise\Scheduler\ScheduledTaskRunner;
use Illuminate\Console\Command;

class HeosSchedulerRunCommand extends Command
{
    protected $signature = 'heos:scheduler:run';

    protected $description = 'Run due HEOS scheduled tasks';

    public function handle(ScheduledTaskRunner $runner): int
    {
        $result = $runner->runDueTasks();

        $this->components->info(sprintf(
            'Scheduler run complete. Processed: %d, Skipped: %d, Failed: %d',
            $result['processed'],
            $result['skipped'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
