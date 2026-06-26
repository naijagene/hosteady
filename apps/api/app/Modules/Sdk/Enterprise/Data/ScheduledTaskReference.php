<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class ScheduledTaskReference
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $publicId,
        public string $taskType,
        public string $status,
        public string $displayName,
        public bool $enabled,
        public ?string $description = null,
        public ?string $moduleKey = null,
        public ?EntityReference $entityReference = null,
        public ?string $cronExpression = null,
        public ?string $runAt = null,
        public ?string $timezone = null,
        public array $payload = [],
        public ?string $lastRunAt = null,
        public ?string $nextRunAt = null,
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
            'task_type' => $this->taskType,
            'status' => $this->status,
            'display_name' => $this->displayName,
            'enabled' => $this->enabled,
            'description' => $this->description,
            'module_key' => $this->moduleKey,
            'entity_reference' => $this->entityReference?->toArray(),
            'cron_expression' => $this->cronExpression,
            'run_at' => $this->runAt,
            'timezone' => $this->timezone,
            'payload' => $this->payload,
            'last_run_at' => $this->lastRunAt,
            'next_run_at' => $this->nextRunAt,
            'created_at' => $this->createdAt,
        ];
    }
}
