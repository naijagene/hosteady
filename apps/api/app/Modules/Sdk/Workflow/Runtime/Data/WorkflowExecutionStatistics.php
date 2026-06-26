<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowExecutionStatistics implements \JsonSerializable
{
    public function __construct(
        public int $runningInstances,
        public int $completedToday,
        public int $failedToday,
        public int $activeExecutions,
        public ?int $averageDurationMs = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'running_instances' => $this->runningInstances,
            'completed_today' => $this->completedToday,
            'failed_today' => $this->failedToday,
            'active_executions' => $this->activeExecutions,
            'average_duration_ms' => $this->averageDurationMs,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
