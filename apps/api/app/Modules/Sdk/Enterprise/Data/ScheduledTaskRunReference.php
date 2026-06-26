<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class ScheduledTaskRunReference
{
    /**
     * @param  array<string, mixed>|null  $output
     */
    public function __construct(
        public string $publicId,
        public string $status,
        public ?string $scheduledTaskPublicId = null,
        public ?string $platformJobPublicId = null,
        public ?string $errorMessage = null,
        public ?array $output = null,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
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
            'status' => $this->status,
            'scheduled_task_public_id' => $this->scheduledTaskPublicId,
            'platform_job_public_id' => $this->platformJobPublicId,
            'error_message' => $this->errorMessage,
            'output' => $this->output,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
