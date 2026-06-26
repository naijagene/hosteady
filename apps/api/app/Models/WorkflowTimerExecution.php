<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerExecutionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTimerExecution extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_timer_id',
        'status',
        'executed_at',
        'error_message',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkflowTriggerExecutionStatus::class,
            'metadata' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function timer(): BelongsTo
    {
        return $this->belongsTo(WorkflowTimer::class, 'workflow_timer_id');
    }
}
