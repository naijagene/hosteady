<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformNotification extends Model
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
        'recipient_membership_id',
        'module_key',
        'type',
        'title',
        'body',
        'data',
        'subject_reference',
        'channel',
        'status',
        'read_at',
        'deleted_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'subject_reference' => 'array',
            'status' => NotificationDeliveryStatus::class,
            'read_at' => 'datetime',
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

    public function recipientMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'recipient_membership_id');
    }
}
