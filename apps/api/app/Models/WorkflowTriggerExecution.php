<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerExecutionStatus;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowTriggerSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTriggerExecution extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'workflow_automation_rule_id',
        'trigger_source',
        'status',
        'workflow_instance_id',
        'event_name',
        'error_message',
        'metadata',
        'executed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_source' => WorkflowTriggerSource::class,
            'status' => WorkflowTriggerExecutionStatus::class,
            'metadata' => 'array',
            'executed_at' => 'datetime',
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

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowAutomationRule::class, 'workflow_automation_rule_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }
}
