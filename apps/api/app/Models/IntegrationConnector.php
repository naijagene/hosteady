<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationAuthType;
use App\Modules\Sdk\Integration\Enums\IntegrationConnectorType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationConnector extends Model
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
        'module_key',
        'connector_key',
        'name',
        'description',
        'connector_type',
        'auth_type',
        'status',
        'config_json',
        'metadata',
        'created_by_user_id',
        'created_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'metadata' => 'array',
            'connector_type' => IntegrationConnectorType::class,
            'auth_type' => IntegrationAuthType::class,
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

    public function endpoints(): HasMany
    {
        return $this->hasMany(IntegrationEndpoint::class);
    }
}
