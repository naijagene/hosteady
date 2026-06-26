<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowExecutionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecutionStep extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_instance_id',
        'node_id',
        'node_type',
        'status',
        'started_at',
        'completed_at',
        'duration_ms',
        'result',
        'warnings',
        'errors',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => 'array',
            'warnings' => 'array',
            'errors' => 'array',
            'metadata' => 'array',
            'status' => WorkflowExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }
}
