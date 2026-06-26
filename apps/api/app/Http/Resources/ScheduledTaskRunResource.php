<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRunReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduledTaskRunReference */
class ScheduledTaskRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ScheduledTaskRunReference $run */
        $run = $this->resource;

        return [
            'public_id' => $run->publicId,
            'status' => $run->status,
            'scheduled_task_public_id' => $run->scheduledTaskPublicId,
            'platform_job_public_id' => $run->platformJobPublicId,
            'error_message' => $run->errorMessage,
            'output' => $run->output,
            'started_at' => $run->startedAt,
            'finished_at' => $run->finishedAt,
            'created_at' => $run->createdAt,
        ];
    }
}
