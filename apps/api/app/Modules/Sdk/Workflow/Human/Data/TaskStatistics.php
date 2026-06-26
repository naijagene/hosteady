<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class TaskStatistics implements \JsonSerializable
{
    public function __construct(
        public int $pending,
        public int $assigned,
        public int $completed,
        public int $overdue,
        public int $pendingApprovals,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pending' => $this->pending,
            'assigned' => $this->assigned,
            'completed' => $this->completed,
            'overdue' => $this->overdue,
            'pending_approvals' => $this->pendingApprovals,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
