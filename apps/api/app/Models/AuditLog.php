<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditCategory;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'occurred_at',
        'scope',
        'organization_id',
        'workspace_id',
        'actor_user_id',
        'actor_membership_id',
        'actor_type',
        'ip_address',
        'user_agent',
        'request_id',
        'category',
        'action',
        'event_version',
        'severity',
        'summary',
        'entity_type',
        'entity_public_id',
        'entity_label',
        'before_state',
        'after_state',
        'metadata',
        'retention_class',
        'expires_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'scope' => AuditScope::class,
            'actor_type' => AuditActorType::class,
            'category' => AuditCategory::class,
            'action' => AuditAction::class,
            'severity' => AuditSeverity::class,
            'retention_class' => AuditRetentionClass::class,
            'before_state' => 'array',
            'after_state' => 'array',
            'metadata' => 'array',
            'event_version' => 'integer',
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

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'actor_membership_id');
    }
}
