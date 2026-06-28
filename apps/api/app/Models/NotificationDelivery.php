<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Notification\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationDelivery extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'enterprise_notification_id',
        'notification_public_id',
        'organization_id',
        'workspace_id',
        'recipient_membership_id',
        'channel',
        'status',
        'delivered_at',
        'read_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => NotificationStatus::class,
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function enterpriseNotification(): BelongsTo
    {
        return $this->belongsTo(EnterpriseNotification::class);
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
