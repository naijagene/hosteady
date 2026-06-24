<?php

namespace App\Models;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationMembership extends Model
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
        'user_id',
        'status',
        'joined_at',
        'default_workspace_id',
        'title',
        'invited_by_user_id',
        'join_method',
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
            'status' => MembershipStatus::class,
            'joined_at' => 'datetime',
            'join_method' => JoinMethod::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'default_workspace_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function memberRoles(): HasMany
    {
        return $this->hasMany(OrganizationMemberRole::class);
    }

    public function acceptedInvitation(): HasMany
    {
        return $this->hasMany(Invitation::class, 'accepted_membership_id');
    }
}
