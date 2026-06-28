<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationEndpointType;
use App\Modules\Sdk\Integration\Enums\IntegrationEventDirection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationEndpoint extends Model
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
        'integration_connector_id',
        'endpoint_key',
        'name',
        'endpoint_type',
        'direction',
        'status',
        'url_template',
        'method',
        'headers_json',
        'body_template_json',
        'auth_reference',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers_json' => 'array',
            'body_template_json' => 'array',
            'auth_reference' => 'array',
            'metadata' => 'array',
            'endpoint_type' => IntegrationEndpointType::class,
            'direction' => IntegrationEventDirection::class,
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

    public function connector(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnector::class, 'integration_connector_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(IntegrationDispatch::class);
    }
}
