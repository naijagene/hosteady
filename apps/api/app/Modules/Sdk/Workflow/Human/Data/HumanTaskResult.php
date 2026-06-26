<?php

namespace App\Modules\Sdk\Workflow\Human\Data;

readonly class HumanTaskResult implements \JsonSerializable
{
    public function __construct(
        public HumanTaskReference $task,
        public ?ApprovalReference $approval = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task' => $this->task->toArray(),
            'approval' => $this->approval?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
