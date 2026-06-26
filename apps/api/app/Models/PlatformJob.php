<?php

namespace App\Models;

use App\Enums\PlatformJobPriority;
use App\Enums\PlatformJobStatus;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformJob extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'module_key',
        'job_type',
        'display_name',
        'queue_name',
        'status',
        'priority',
        'payload',
        'result',
        'error_message',
        'error_class',
        'attempts',
        'max_attempts',
        'correlation_id',
        'entity_reference',
        'scheduled_task_id',
        'started_at',
        'finished_at',
        'failed_at',
        'cancelled_at',
        'created_by_user_id',
        'created_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'entity_reference' => 'array',
            'status' => PlatformJobStatus::class,
            'priority' => PlatformJobPriority::class,
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class);
    }
}
