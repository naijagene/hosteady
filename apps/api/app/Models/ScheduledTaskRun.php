<?php

namespace App\Models;

use App\Enums\ScheduledTaskRunStatus;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTaskRun extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'scheduled_task_id',
        'platform_job_id',
        'status',
        'started_at',
        'finished_at',
        'error_message',
        'output',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'output' => 'array',
            'status' => ScheduledTaskRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class);
    }

    public function platformJob(): BelongsTo
    {
        return $this->belongsTo(PlatformJob::class);
    }
}
