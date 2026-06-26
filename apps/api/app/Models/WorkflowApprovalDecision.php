<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalDecisionType;
use App\Modules\Sdk\Workflow\Human\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApprovalDecision extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workflow_human_task_id',
        'decision_type',
        'status',
        'decided_by_user_id',
        'decided_by_membership_id',
        'decided_at',
        'comment',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'decision_type' => ApprovalDecisionType::class,
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkflowHumanTask::class, 'workflow_human_task_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function decidedByMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'decided_by_membership_id');
    }
}
