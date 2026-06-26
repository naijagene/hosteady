<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Automation\Enums\WorkflowAutomationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTriggerSubscription extends Model
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
        'event_name',
        'status',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowAutomationRule::class, 'workflow_automation_rule_id');
    }
}
