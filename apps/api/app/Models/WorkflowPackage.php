<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageType;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowPackage extends Model
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
        'package_key',
        'name',
        'description',
        'author',
        'license',
        'visibility',
        'type',
        'status',
        'tags',
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
            'tags' => 'array',
            'metadata' => 'array',
            'status' => WorkflowPackageStatus::class,
            'visibility' => WorkflowPackageVisibility::class,
            'type' => WorkflowPackageType::class,
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

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowPackageVersion::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(WorkflowPackageDependency::class);
    }

    public function installs(): HasMany
    {
        return $this->hasMany(WorkflowPackageInstall::class);
    }
}
