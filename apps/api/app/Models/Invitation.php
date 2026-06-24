<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Models\Concerns\HasHeosAudit;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends Model
{
    use HasHeosAudit, HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'invitation_code',
        'organization_id',
        'email',
        'invited_by_user_id',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'accepted_membership_id',
        'message',
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
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function acceptedMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'accepted_membership_id');
    }

    public function invitationRoles(): HasMany
    {
        return $this->hasMany(InvitationRole::class);
    }
}
