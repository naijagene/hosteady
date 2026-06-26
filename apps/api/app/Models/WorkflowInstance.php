<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
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
        'workflow_definition_id',
        'workflow_version_id',
        'status',
        'current_node_id',
        'input_payload',
        'result',
        'warnings',
        'errors',
        'metadata',
        'started_at',
        'completed_at',
        'duration_ms',
        'created_by_user_id',
        'created_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'result' => 'array',
            'warnings' => 'array',
            'errors' => 'array',
            'metadata' => 'array',
            'status' => WorkflowInstanceStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowExecutionStep::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(WorkflowExecutionVariable::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkflowExecutionEvent::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }
}
