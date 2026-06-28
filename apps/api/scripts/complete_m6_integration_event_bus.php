<?php

declare(strict_types=1);

/**
 * M6-005 Enterprise Integration & Event Bus — complete scaffold generator.
 * Run: php scripts/complete_m6_integration_event_bus.php
 */

$base = dirname(__DIR__);
$count = 0;

function writeFile(string $path, string $content): void
{
    global $base, $count;
    $full = $base.'/'.$path;
    $dir = dirname($full);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($full, $content);
    echo "Wrote: {$path}\n";
    $count++;
}

// --- Models ---
$models = [
    'IntegrationEvent' => <<<'PHP'
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

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'event_name', 'event_version',
        'direction', 'source_type', 'source_module_key', 'source_entity_key', 'source_public_id',
        'correlation_id', 'idempotency_key', 'status', 'payload_json', 'headers_json', 'metadata',
        'occurred_at', 'published_at', 'created_at',
    ];

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

PHP,
    'IntegrationEventSubscription' => <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationEventSubscription extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'module_key', 'subscription_key',
        'event_pattern', 'endpoint_key', 'status', 'filters_json', 'transform_json',
        'retry_policy_json', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'transform_json' => 'array',
            'retry_policy_json' => 'array',
            'metadata' => 'array',
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
}

PHP,
    'IntegrationConnector' => <<<'PHP'
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

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'module_key', 'connector_key', 'name',
        'description', 'connector_type', 'auth_type', 'status', 'config_json', 'metadata',
        'created_by_user_id', 'created_membership_id',
    ];

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

PHP,
    'IntegrationEndpoint' => <<<'PHP'
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

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'integration_connector_id', 'endpoint_key',
        'name', 'endpoint_type', 'direction', 'status', 'url_template', 'method',
        'headers_json', 'body_template_json', 'auth_reference', 'metadata',
    ];

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

PHP,
    'IntegrationCredential' => <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationAuthType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationCredential extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'connector_key', 'credential_key',
        'auth_type', 'encrypted_payload', 'metadata', 'rotated_at',
        'created_by_user_id', 'created_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'auth_type' => IntegrationAuthType::class,
            'rotated_at' => 'datetime',
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
}

PHP,
    'IntegrationMapping' => <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Integration\Enums\IntegrationTransformType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationMapping extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'module_key', 'mapping_key',
        'source_schema', 'target_schema', 'mapping_json', 'transform_type', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source_schema' => 'array',
            'target_schema' => 'array',
            'mapping_json' => 'array',
            'metadata' => 'array',
            'transform_type' => IntegrationTransformType::class,
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
}

PHP,
    'IntegrationDispatch' => <<<'PHP'
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

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'integration_event_id',
        'integration_endpoint_id', 'subscription_key', 'status', 'attempt', 'max_attempts',
        'request_json', 'response_json', 'error_message', 'dispatched_at', 'completed_at',
        'next_retry_at', 'metadata',
    ];

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

PHP,
    'IntegrationDeadLetter' => <<<'PHP'
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

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'integration_event_id',
        'integration_dispatch_id', 'status', 'reason', 'payload_json', 'error_message',
        'metadata', 'created_at', 'resolved_at', 'resolved_by_user_id', 'resolved_by_membership_id',
    ];

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

PHP,
    'IntegrationActivityLog' => <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationActivityLog extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'integration_event_id',
        'integration_connector_id', 'integration_endpoint_id', 'action',
        'before_state', 'after_state', 'actor_user_id', 'actor_membership_id', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'metadata' => 'array',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(IntegrationEvent::class, 'integration_event_id');
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnector::class, 'integration_connector_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'integration_endpoint_id');
    }
}

PHP,
];

foreach ($models as $name => $content) {
    writeFile("app/Models/{$name}.php", $content);
}

echo "\nGenerated {$count} files.\n";
