<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformJobReference
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $result
     */
    public function __construct(
        public string $publicId,
        public string $jobType,
        public string $status,
        public string $priority,
        public ?string $displayName = null,
        public ?string $moduleKey = null,
        public ?EntityReference $entityReference = null,
        public array $payload = [],
        public ?array $result = null,
        public ?string $errorMessage = null,
        public int $attempts = 0,
        public int $maxAttempts = 3,
        public ?string $correlationId = null,
        public ?string $queueName = null,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
        public ?string $failedAt = null,
        public ?string $cancelledAt = null,
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'job_type' => $this->jobType,
            'status' => $this->status,
            'priority' => $this->priority,
            'display_name' => $this->displayName,
            'module_key' => $this->moduleKey,
            'entity_reference' => $this->entityReference?->toArray(),
            'payload' => $this->payload,
            'result' => $this->result,
            'error_message' => $this->errorMessage,
            'attempts' => $this->attempts,
            'max_attempts' => $this->maxAttempts,
            'correlation_id' => $this->correlationId,
            'queue_name' => $this->queueName,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'failed_at' => $this->failedAt,
            'cancelled_at' => $this->cancelledAt,
            'created_at' => $this->createdAt,
        ];
    }
}
