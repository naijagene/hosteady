<?php

namespace App\Services\Enterprise\Workflow\Automation;

use App\Models\WorkflowTimer;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTimerStatus;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;

class WorkflowTimerRunner
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'workflow_timers',
        'workflow_timer_executions',
        'workflow_instances',
    ];

    public function __construct(
        private readonly WorkflowTimerService $timerService,
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /**
     * @return array{processed: int, succeeded: int, failed: int, missing_tables: list<string>, warnings: list<string>}
     */
    public function runDueTimers(): array
    {
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        if ($missingTables !== []) {
            return [
                'processed' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'missing_tables' => $missingTables,
                'warnings' => array_map(
                    fn (string $table): string => $this->tableGuard->missingTableWarning($table),
                    $missingTables,
                ),
            ];
        }

        if (! (bool) config('heos.enterprise.automation.enabled', true)) {
            return [
                'processed' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'missing_tables' => [],
                'warnings' => [],
            ];
        }

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        WorkflowTimer::query()
            ->where('status', WorkflowTimerStatus::Active)
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->chunkById(50, function ($timers) use (&$processed, &$succeeded, &$failed) {
                foreach ($timers as $timer) {
                    $processed++;

                    try {
                        $this->timerService->executeDueTimer($timer->public_id);
                        $succeeded++;
                    } catch (\Throwable) {
                        $failed++;
                    }
                }
            });

        return [
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'missing_tables' => [],
            'warnings' => [],
        ];
    }
}
