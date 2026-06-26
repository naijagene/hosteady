<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Enterprise\Data\ScheduledTaskReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduledTaskReference */
class ScheduledTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ScheduledTaskReference $task */
        $task = $this->resource;

        return [
            'public_id' => $task->publicId,
            'task_type' => $task->taskType,
            'status' => $task->status,
            'display_name' => $task->displayName,
            'description' => $task->description,
            'enabled' => $task->enabled,
            'module_key' => $task->moduleKey,
            'entity_reference' => $task->entityReference?->toArray(),
            'cron_expression' => $task->cronExpression,
            'run_at' => $task->runAt,
            'timezone' => $task->timezone,
            'payload' => $task->payload,
            'last_run_at' => $task->lastRunAt,
            'next_run_at' => $task->nextRunAt,
            'created_at' => $task->createdAt,
        ];
    }
}
