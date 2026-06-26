<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\PlatformJobReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlatformJobReference */
class PlatformJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PlatformJobReference $job */
        $job = $this->resource;

        return [
            'public_id' => $job->publicId,
            'job_type' => $job->jobType,
            'status' => $job->status,
            'priority' => $job->priority,
            'display_name' => $job->displayName,
            'module_key' => $job->moduleKey,
            'entity_reference' => $job->entityReference?->toArray(),
            'payload' => $job->payload,
            'result' => $job->result,
            'error_message' => $job->errorMessage,
            'attempts' => $job->attempts,
            'max_attempts' => $job->maxAttempts,
            'correlation_id' => $job->correlationId,
            'queue_name' => $job->queueName,
            'started_at' => $job->startedAt,
            'finished_at' => $job->finishedAt,
            'failed_at' => $job->failedAt,
            'cancelled_at' => $job->cancelledAt,
            'created_at' => $job->createdAt,
        ];
    }
}
