<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationEventDirection;
use App\Modules\Sdk\Integration\Enums\IntegrationEventSourceType;
use App\Modules\Sdk\Integration\Enums\IntegrationEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationEvent extends Model
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
        'event_name',
        'event_version',
        'direction',
        'source_type',
        'source_module_key',
        'source_entity_key',
        'source_public_id',
        'correlation_id',
        'idempotency_key',
        'status',
        'payload_json',
        'headers_json',
        'metadata',
        'occurred_at',
        'published_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'headers_json' => 'array',
            'metadata' => 'array',
            'direction' => IntegrationEventDirection::class,
            'source_type' => IntegrationEventSourceType::class,
            'status' => IntegrationEventStatus::class,
            'occurred_at' => 'datetime',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
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

    public function dispatches(): HasMany
    {
        return $this->hasMany(IntegrationDispatch::class);
    }

    public function deadLetters(): HasMany
    {
        return $this->hasMany(IntegrationDeadLetter::class);
    }
}
