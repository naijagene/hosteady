<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowAutomationStatistics implements \JsonSerializable
{
    public function __construct(
        public int $activeRules,
        public int $disabledRules,
        public int $triggerExecutionsToday,
        public int $failedTriggersToday,
        public int $activeTimers,
        public int $dueTimers,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'active_rules' => $this->activeRules,
            'disabled_rules' => $this->disabledRules,
            'trigger_executions_today' => $this->triggerExecutionsToday,
            'failed_triggers_today' => $this->failedTriggersToday,
            'active_timers' => $this->activeTimers,
            'due_timers' => $this->dueTimers,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
