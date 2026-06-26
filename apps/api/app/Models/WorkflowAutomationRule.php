<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowAutomationRule extends Model
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
        'trigger_type',
        'trigger_config',
        'status',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'metadata' => 'array',
            'status' => WorkflowAutomationStatus::class,
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

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WorkflowTriggerSubscription::class);
    }

    public function triggerExecutions(): HasMany
    {
        return $this->hasMany(WorkflowTriggerExecution::class);
    }
}
