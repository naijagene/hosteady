<?php

namespace App\Modules\Sdk\Workflow\Automation\Data;

readonly class WorkflowTimerExecution implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $timerPublicId,
        public string $status,
        public ?string $executedAt = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'timer_public_id' => $this->timerPublicId,
            'status' => $this->status,
            'executed_at' => $this->executedAt,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
