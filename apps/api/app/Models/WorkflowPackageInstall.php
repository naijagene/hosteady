<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowInstallStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowPackageInstall extends Model
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
        'workflow_package_id',
        'workflow_package_version_id',
        'installed_workflow_definition_id',
        'installed_version',
        'status',
        'installed_at',
        'upgraded_at',
        'rolled_back_at',
        'uninstalled_at',
        'installed_by_user_id',
        'installed_by_membership_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => WorkflowInstallStatus::class,
            'installed_at' => 'datetime',
            'upgraded_at' => 'datetime',
            'rolled_back_at' => 'datetime',
            'uninstalled_at' => 'datetime',
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

    public function workflowPackage(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackage::class);
    }

    public function workflowPackageVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowPackageVersion::class);
    }

    public function installedWorkflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'installed_workflow_definition_id');
    }
}
