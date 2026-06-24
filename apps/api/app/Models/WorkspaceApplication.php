<?php

namespace App\Models;

use App\Enums\WorkspaceApplicationStatus;
use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceApplication extends Model
{
    use HasHeosAudit, HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'organization_application_id',
        'application_id',
        'status',
        'enabled_version',
        'is_bootstrap',
        'enabled_at',
        'enabled_by_user_id',
        'enabled_by_membership_id',
        'created_by_user_id',
        'updated_by_user_id',
        'deleted_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkspaceApplicationStatus::class,
            'is_bootstrap' => 'boolean',
            'enabled_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function organizationApplication(): BelongsTo
    {
        return $this->belongsTo(OrganizationApplication::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by_user_id');
    }

    public function enabledByMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'enabled_by_membership_id');
    }
}
