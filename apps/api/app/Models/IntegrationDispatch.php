<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationDeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationDispatch extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'integration_event_id',
        'integration_endpoint_id',
        'subscription_key',
        'status',
        'attempt',
        'max_attempts',
        'request_json',
        'response_json',
        'error_message',
        'dispatched_at',
        'completed_at',
        'next_retry_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_json' => 'array',
            'response_json' => 'array',
            'metadata' => 'array',
            'status' => IntegrationDeliveryStatus::class,
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'next_retry_at' => 'datetime',
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

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'integration_endpoint_id');
    }

    public function deadLetters(): HasMany
    {
        return $this->hasMany(IntegrationDeadLetter::class);
    }
}
