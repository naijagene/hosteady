<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationDeadLetterStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationDeadLetter extends Model
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
        'organization_id',
        'workspace_id',
        'integration_event_id',
        'integration_dispatch_id',
        'status',
        'reason',
        'payload_json',
        'error_message',
        'metadata',
        'created_at',
        'resolved_at',
        'resolved_by_user_id',
        'resolved_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'metadata' => 'array',
            'status' => IntegrationDeadLetterStatus::class,
            'created_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class, 'integration_event_id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(IntegrationDispatch::class, 'integration_dispatch_id');
    }
}
