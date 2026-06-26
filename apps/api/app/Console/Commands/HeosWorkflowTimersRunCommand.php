<?php

namespace App\Console\Commands;

use App\Services\Enterprise\Workflow\Automation\WorkflowTimerRunner;
use Illuminate\Console\Command;

class HeosWorkflowTimersRunCommand extends Command
{
    protected $signature = 'heos:workflow:timers:run';

    protected $description = 'Run due HEOS workflow timers';

    public function handle(WorkflowTimerRunner $runner): int
    {
        $result = $runner->runDueTimers();

        foreach ($result['warnings'] as $warning) {
            $this->components->warn($warning);
        }

        if ($result['missing_tables'] !== []) {
            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Workflow timers run complete. Processed: %d, Succeeded: %d, Failed: %d',
            $result['processed'],
            $result['succeeded'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
