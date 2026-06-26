<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalStatus;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;
use App\Modules\Sdk\Workflow\Human\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowHumanTask extends Model
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
        'workflow_instance_id',
        'node_id',
        'task_type',
        'title',
        'description',
        'status',
        'priority',
        'approval_status',
        'assignee_user_id',
        'assignee_membership_id',
        'assignee_role_key',
        'assigned_at',
        'opened_at',
        'completed_at',
        'due_at',
        'completed_by_user_id',
        'completed_by_membership_id',
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
            'metadata' => 'array',
            'status' => HumanTaskStatus::class,
            'priority' => TaskPriority::class,
            'approval_status' => ApprovalStatus::class,
            'assigned_at' => 'datetime',
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_at' => 'datetime',
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

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowTaskAssignment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkflowTaskComment::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(WorkflowTaskEscalation::class);
    }

    public function approvalDecision(): HasOne
    {
        return $this->hasOne(WorkflowApprovalDecision::class);
    }

    public function assigneeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assigneeMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'assignee_membership_id');
    }
}
