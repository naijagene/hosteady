<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Notification\Enums\NotificationPriority;
use App\Modules\Sdk\Notification\Enums\NotificationScope;
use App\Modules\Sdk\Notification\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseNotification extends Model
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
        'scope',
        'priority',
        'status',
        'title',
        'body',
        'template_key',
        'merge_data',
        'channels',
        'metadata',
        'sender_membership_id',
        'read_at',
        'deleted_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merge_data' => 'array',
            'channels' => 'array',
            'metadata' => 'array',
            'scope' => NotificationScope::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
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

    public function senderMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'sender_membership_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(NotificationActivity::class);
    }
}
