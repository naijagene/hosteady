<?php

declare(strict_types=1);

$base = dirname(__DIR__);

function writeFile(string $path, string $content): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($path, $content);
}

function dto(string $namespace, string $class, array $fields): string
{
    $props = $from = $to = [];
    foreach ($fields as $name => $type) {
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        $camel = lcfirst($name);
        $props[] = "        public {$type} \${$camel}";
        if ($type === 'array') {
            $from[] = "            {$camel}: is_array(\$data['{$snake}'] ?? \$data['{$name}'] ?? null) ? (\$data['{$snake}'] ?? \$data['{$name}']) : [],";
        } elseif ($type === 'int') {
            $from[] = "            {$camel}: (int) (\$data['{$snake}'] ?? \$data['{$name}'] ?? 0),";
        } elseif ($type === 'float') {
            $from[] = "            {$camel}: (float) (\$data['{$snake}'] ?? \$data['{$name}'] ?? 0),";
        } elseif ($type === 'bool') {
            $from[] = "            {$camel}: (bool) (\$data['{$snake}'] ?? \$data['{$name}'] ?? false),";
        } elseif (str_starts_with($type, '?')) {
            $from[] = "            {$camel}: isset(\$data['{$snake}']) ? (string) \$data['{$snake}'] : (isset(\$data['{$name}']) ? (string) \$data['{$name}'] : null),";
        } else {
            $from[] = "            {$camel}: (string) (\$data['{$snake}'] ?? \$data['{$name}'] ?? ''),";
        }
        $to[] = "            '{$snake}' => \$this->{$camel},";
    }

    return <<<PHP
<?php

namespace {$namespace};

readonly class {$class} implements \\JsonSerializable
{
    public function __construct(
{$props ? implode(",\n", $props) : ''}
    ) {
    }

    public static function fromArray(array \$data): self
    {
        return new self(
{$from ? implode("\n", $from) : ''}
        );
    }

    public function toArray(): array
    {
        return [
{$to ? implode("\n", $to) : ''}
        ];
    }

    public function jsonSerialize(): array
    {
        return \$this->toArray();
    }
}

PHP;
}

function enumFile(string $namespace, string $class, array $cases): string
{
    $body = implode("\n", array_map(fn (string $name, string $value) => "    case {$name} = '{$value}';", array_keys($cases), $cases));

    return <<<PHP
<?php

namespace {$namespace};

enum {$class}: string
{
{$body}
}

PHP;
}

function contractFile(string $namespace, string $class, string $methods): string
{
    return <<<PHP
<?php

namespace {$namespace};

interface {$class}
{
{$methods}
}

PHP;
}

function exceptionFile(string $namespace, string $class, string $parent = 'PersonalizationException'): string
{
    return <<<PHP
<?php

namespace {$namespace};

class {$class} extends {$parent}
{
}

PHP;
}

$sdk = $base.'/app/Modules/Sdk/Personalization';
$ns = 'App\\Modules\\Sdk\\Personalization';

writeFile($sdk.'/Exceptions/PersonalizationException.php', exceptionFile("{$ns}\\Exceptions", 'PersonalizationException', '\\Exception'));
foreach ([
    'PersonalizationNotFoundException',
    'PersonalizationValidationException',
    'PersonalizationRuntimeException',
    'PersonalizationPreferenceException',
] as $exception) {
    writeFile($sdk.'/Exceptions/'.$exception.'.php', exceptionFile("{$ns}\\Exceptions", $exception));
}

$enums = [
    'PersonalizationScope' => [
        'Global' => 'global',
        'Organization' => 'organization',
        'Application' => 'application',
        'Workspace' => 'workspace',
        'Membership' => 'membership',
        'User' => 'user',
    ],
    'PreferenceValueType' => [
        'String' => 'string',
        'Boolean' => 'boolean',
        'Integer' => 'integer',
        'Decimal' => 'decimal',
        'Json' => 'json',
        'Enum' => 'enum',
        'List' => 'list',
        'Map' => 'map',
    ],
    'OnboardingStatus' => [
        'Started' => 'started',
        'InProgress' => 'in_progress',
        'Completed' => 'completed',
    ],
];
foreach ($enums as $class => $cases) {
    writeFile($sdk.'/Enums/'.$class.'.php', enumFile("{$ns}\\Enums", $class, $cases));
}

$dtos = [
    'PersonalizationProfile' => [
        'publicId' => 'string',
        'scope' => 'string',
        'name' => 'string',
        'applicationPublicId' => '?string',
        'workspacePublicId' => '?string',
        'membershipPublicId' => '?string',
        'userPublicId' => '?string',
        'isDefault' => 'bool',
        'metadata' => 'array',
    ],
    'Preference' => [
        'publicId' => 'string',
        'profilePublicId' => '?string',
        'scope' => 'string',
        'key' => 'string',
        'valueType' => 'string',
        'value' => 'array',
        'metadata' => 'array',
    ],
    'FavoriteItem' => [
        'publicId' => 'string',
        'profilePublicId' => '?string',
        'scope' => 'string',
        'subjectType' => 'string',
        'subjectPublicId' => 'string',
        'label' => '?string',
        'position' => 'int',
        'metadata' => 'array',
    ],
    'RecentItem' => [
        'publicId' => 'string',
        'profilePublicId' => '?string',
        'scope' => 'string',
        'subjectType' => 'string',
        'subjectPublicId' => 'string',
        'title' => '?string',
        'visitedAt' => '?string',
        'metadata' => 'array',
    ],
    'Shortcut' => [
        'publicId' => 'string',
        'profilePublicId' => '?string',
        'scope' => 'string',
        'shortcutKey' => 'string',
        'label' => 'string',
        'icon' => '?string',
        'route' => '?string',
        'target' => '?string',
        'position' => 'int',
        'isActive' => 'bool',
        'metadata' => 'array',
    ],
    'OnboardingState' => [
        'publicId' => 'string',
        'profilePublicId' => '?string',
        'scope' => 'string',
        'flowKey' => 'string',
        'currentStep' => '?string',
        'completedSteps' => 'array',
        'dismissedTips' => 'array',
        'status' => 'string',
        'completedAt' => '?string',
        'metadata' => 'array',
    ],
    'PersonalizationStatistics' => [
        'profiles' => 'int',
        'preferences' => 'int',
        'favorites' => 'int',
        'recentItems' => 'int',
        'shortcuts' => 'int',
        'onboardingStates' => 'int',
    ],
    'PersonalizationHealthReport' => [
        'enabled' => 'bool',
        'healthy' => 'bool',
        'status' => 'string',
        'warnings' => 'array',
        'missingTables' => 'array',
        'statistics' => 'array',
    ],
    'PersonalizationRuntimePayload' => [
        'profile' => 'array',
        'preferences' => 'array',
        'favorites' => 'array',
        'recent' => 'array',
        'shortcuts' => 'array',
        'quickActions' => 'array',
        'onboarding' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'warnings' => 'array',
    ],
];
foreach ($dtos as $class => $fields) {
    writeFile($sdk.'/Data/'.$class.'.php', dto("{$ns}\\Data", $class, $fields));
}

writeFile($sdk.'/Contracts/PersonalizationPreferenceStore.php', contractFile("{$ns}\\Contracts", 'PersonalizationPreferenceStore', <<<'METHODS'
    public function upsert(\App\Support\Tenant\TenantContext $context, string $key, string $valueType, mixed $value, ?string $scope = null, array $metadata = []): \App\Modules\Sdk\Personalization\Data\Preference;

    /** @return list<\App\Modules\Sdk\Personalization\Data\Preference> */
    public function list(\App\Support\Tenant\TenantContext $context): array;
METHODS));

writeFile($sdk.'/Contracts/PersonalizationFavoriteStore.php', contractFile("{$ns}\\Contracts", 'PersonalizationFavoriteStore', <<<'METHODS'
    public function add(\App\Support\Tenant\TenantContext $context, string $subjectType, string $subjectPublicId, ?string $label = null, array $metadata = []): \App\Modules\Sdk\Personalization\Data\FavoriteItem;

    /** @return list<\App\Modules\Sdk\Personalization\Data\FavoriteItem> */
    public function list(\App\Support\Tenant\TenantContext $context): array;

    public function remove(\App\Support\Tenant\TenantContext $context, string $favoritePublicId): void;
METHODS));

writeFile($sdk.'/Contracts/PersonalizationRuntimeComposer.php', contractFile("{$ns}\\Contracts", 'PersonalizationRuntimeComposer', <<<'METHODS'
    public function compose(\App\Support\Tenant\TenantContext $context): \App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
METHODS));

writeFile($base.'/database/migrations/2026_07_04_100210_create_personalization_framework_tables.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalization_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('name', 191)->default('Default');
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['organization_id', 'workspace_id', 'scope'], 'personalization_profiles_scope_idx');
        });

        Schema::create('personalization_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('profile_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('preference_key', 191);
            $table->string('value_type', 32)->default('string');
            $table->text('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 18, 6)->nullable();
            $table->json('value_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'preference_key'], 'personalization_preferences_key_idx');
        });

        Schema::create('personalization_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('profile_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('subject_type', 64);
            $table->string('subject_public_id', 191);
            $table->string('label', 191)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'subject_type'], 'personalization_favorites_subject_idx');
        });

        Schema::create('personalization_recent_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('profile_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('subject_type', 64);
            $table->string('subject_public_id', 191);
            $table->string('title', 191)->nullable();
            $table->timestampTz('visited_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'visited_at'], 'personalization_recent_items_visit_idx');
        });

        Schema::create('personalization_shortcuts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('profile_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('shortcut_key', 128);
            $table->string('label', 191);
            $table->string('icon', 64)->nullable();
            $table->string('route', 191)->nullable();
            $table->string('target', 191)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'shortcut_key'], 'personalization_shortcuts_key_idx');
        });

        Schema::create('personalization_onboarding_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('profile_id')->nullable();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->uuid('membership_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('scope', 32)->default('workspace');
            $table->string('flow_key', 128);
            $table->string('current_step', 128)->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('dismissed_tips')->nullable();
            $table->string('status', 32)->default('started');
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'flow_key'], 'personalization_onboarding_flow_idx');
        });

        Schema::create('personalization_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('profile_id')->nullable();
            $table->string('action', 128);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->foreign('profile_id')->references('id')->on('personalization_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalization_activity_logs');
        Schema::dropIfExists('personalization_onboarding_states');
        Schema::dropIfExists('personalization_shortcuts');
        Schema::dropIfExists('personalization_recent_items');
        Schema::dropIfExists('personalization_favorites');
        Schema::dropIfExists('personalization_preferences');
        Schema::dropIfExists('personalization_profiles');
    }
};
PHP);

writeFile($base.'/app/Models/PersonalizationProfile.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationProfile extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'name', 'is_default', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'bool',
            'metadata' => 'array',
        ];
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(PersonalizationPreference::class, 'profile_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(PersonalizationFavorite::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationPreference.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationPreference extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_preferences';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'profile_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'preference_key', 'value_type', 'value_string', 'value_boolean', 'value_integer',
        'value_decimal', 'value_json', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'value_boolean' => 'bool',
            'value_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationFavorite.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationFavorite extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_favorites';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'profile_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'subject_type', 'subject_public_id', 'label', 'position', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationRecentItem.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationRecentItem extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_recent_items';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'profile_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'subject_type', 'subject_public_id', 'title', 'visited_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationShortcut.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationShortcut extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_shortcuts';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'profile_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'shortcut_key', 'label', 'icon', 'route', 'target', 'position', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationOnboardingState.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalizationOnboardingState extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'personalization_onboarding_states';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'profile_id', 'organization_id', 'workspace_id', 'application_id', 'membership_id', 'user_id',
        'scope', 'flow_key', 'current_step', 'completed_steps', 'dismissed_tips', 'status', 'completed_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'dismissed_tips' => 'array',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Models/PersonalizationActivityLog.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalizationActivityLog extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'personalization_activity_logs';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'profile_id',
        'action', 'before_state', 'after_state', 'actor_user_id', 'actor_membership_id',
        'metadata', 'created_at',
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

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalizationProfile::class, 'profile_id');
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationMapper.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\OrganizationMembership;
use App\Models\PersonalizationFavorite;
use App\Models\PersonalizationOnboardingState;
use App\Models\PersonalizationPreference;
use App\Models\PersonalizationProfile;
use App\Models\PersonalizationRecentItem;
use App\Models\PersonalizationShortcut;
use App\Models\User;
use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Modules\Sdk\Personalization\Data\OnboardingState;
use App\Modules\Sdk\Personalization\Data\PersonalizationProfile as PersonalizationProfileDto;
use App\Modules\Sdk\Personalization\Data\Preference;
use App\Modules\Sdk\Personalization\Data\RecentItem;
use App\Modules\Sdk\Personalization\Data\Shortcut;
use Illuminate\Database\Eloquent\Builder;

class PersonalizationMapper
{
    public static function profile(PersonalizationProfile $model): PersonalizationProfileDto
    {
        return new PersonalizationProfileDto(
            publicId: $model->public_id,
            scope: $model->scope,
            name: $model->name,
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
            workspacePublicId: self::resolveWorkspacePublicId($model->workspace_id),
            membershipPublicId: self::resolveMembershipPublicId($model->membership_id),
            userPublicId: self::resolveUserPublicId($model->user_id),
            isDefault: (bool) $model->is_default,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function preference(PersonalizationPreference $model): Preference
    {
        return new Preference(
            publicId: $model->public_id,
            profilePublicId: self::resolveProfilePublicId($model->profile_id),
            scope: $model->scope,
            key: $model->preference_key,
            valueType: $model->value_type,
            value: self::decodePreferenceValue($model),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function favorite(PersonalizationFavorite $model): FavoriteItem
    {
        return new FavoriteItem(
            publicId: $model->public_id,
            profilePublicId: self::resolveProfilePublicId($model->profile_id),
            scope: $model->scope,
            subjectType: $model->subject_type,
            subjectPublicId: $model->subject_public_id,
            label: $model->label,
            position: (int) $model->position,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function recent(PersonalizationRecentItem $model): RecentItem
    {
        return new RecentItem(
            publicId: $model->public_id,
            profilePublicId: self::resolveProfilePublicId($model->profile_id),
            scope: $model->scope,
            subjectType: $model->subject_type,
            subjectPublicId: $model->subject_public_id,
            title: $model->title,
            visitedAt: $model->visited_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function shortcut(PersonalizationShortcut $model): Shortcut
    {
        return new Shortcut(
            publicId: $model->public_id,
            profilePublicId: self::resolveProfilePublicId($model->profile_id),
            scope: $model->scope,
            shortcutKey: $model->shortcut_key,
            label: $model->label,
            icon: $model->icon,
            route: $model->route,
            target: $model->target,
            position: (int) $model->position,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function onboarding(PersonalizationOnboardingState $model): OnboardingState
    {
        return new OnboardingState(
            publicId: $model->public_id,
            profilePublicId: self::resolveProfilePublicId($model->profile_id),
            scope: $model->scope,
            flowKey: $model->flow_key,
            currentStep: $model->current_step,
            completedSteps: is_array($model->completed_steps) ? $model->completed_steps : [],
            dismissedTips: is_array($model->dismissed_tips) ? $model->dismissed_tips : [],
            status: $model->status,
            completedAt: $model->completed_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @param  Builder<PersonalizationProfile|PersonalizationPreference|PersonalizationFavorite|PersonalizationRecentItem|PersonalizationShortcut|PersonalizationOnboardingState>  $query
     */
    public static function applyScope(Builder $query, string $organizationId, ?string $workspaceId): Builder
    {
        $query->where('organization_id', $organizationId);
        if ($workspaceId !== null) {
            $query->where(function (Builder $scoped) use ($workspaceId) {
                $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodePreferenceValue(PersonalizationPreference $model): array
    {
        return match ($model->value_type) {
            'boolean' => ['value' => (bool) $model->value_boolean],
            'integer' => ['value' => (int) $model->value_integer],
            'decimal' => ['value' => (float) $model->value_decimal],
            'json', 'map', 'list' => ['value' => is_array($model->value_json) ? $model->value_json : []],
            default => ['value' => $model->value_string],
        };
    }

    /**
     * @param  array{value?: mixed}  $value
     * @return array<string, mixed>
     */
    public static function encodePreferenceValue(string $valueType, array $value): array
    {
        return match ($valueType) {
            'boolean' => ['value_boolean' => (bool) ($value['value'] ?? false)],
            'integer' => ['value_integer' => (int) ($value['value'] ?? 0)],
            'decimal' => ['value_decimal' => (float) ($value['value'] ?? 0)],
            'json', 'map', 'list' => ['value_json' => is_array($value['value'] ?? null) ? $value['value'] : []],
            default => ['value_string' => (string) ($value['value'] ?? '')],
        };
    }

    public static function resolveApplicationId(?string $applicationPublicId): ?string
    {
        if ($applicationPublicId === null || $applicationPublicId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()->where('public_id', $applicationPublicId)->value('id');
    }

    public static function resolveMembershipId(?string $membershipPublicId): ?string
    {
        if ($membershipPublicId === null || $membershipPublicId === '') {
            return null;
        }

        return OrganizationMembership::query()->where('public_id', $membershipPublicId)->value('id');
    }

    private static function resolveProfilePublicId(?string $profileId): ?string
    {
        if ($profileId === null || $profileId === '') {
            return null;
        }

        return PersonalizationProfile::query()->where('id', $profileId)->value('public_id');
    }

    private static function resolveApplicationPublicId(?string $applicationId): ?string
    {
        if ($applicationId === null || $applicationId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()->where('id', $applicationId)->value('public_id');
    }

    private static function resolveWorkspacePublicId(?string $workspaceId): ?string
    {
        if ($workspaceId === null || $workspaceId === '') {
            return null;
        }

        return \App\Models\Workspace::query()->where('id', $workspaceId)->value('public_id');
    }

    private static function resolveMembershipPublicId(?string $membershipId): ?string
    {
        if ($membershipId === null || $membershipId === '') {
            return null;
        }

        return OrganizationMembership::query()->where('id', $membershipId)->value('public_id');
    }

    private static function resolveUserPublicId(?string $userId): ?string
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        return User::query()->where('id', $userId)->value('public_id');
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationTableHealthSupport.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Modules\Sdk\Personalization\Data\PersonalizationStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;

class PersonalizationTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'personalization_profiles',
        'personalization_preferences',
        'personalization_favorites',
        'personalization_recent_items',
        'personalization_shortcuts',
        'personalization_onboarding_states',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
    ) {
    }

    /** @return list<string> */
    public function missingCoreTables(): array
    {
        return $this->tableGuard->missingTables(self::CORE_TABLES);
    }

    public function coreTablesPresent(): bool
    {
        return $this->missingCoreTables() === [];
    }

    public function isTablePresent(string $table): bool
    {
        return $this->tableGuard->missingTables([$table]) === [];
    }

    /** @return list<string> */
    public function warningsForCoreTables(): array
    {
        return array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $this->missingCoreTables(),
        );
    }

    public function emptyStatistics(): PersonalizationStatistics
    {
        return new PersonalizationStatistics(0, 0, 0, 0, 0, 0);
    }

    public function emptyRuntime(): PersonalizationRuntimePayload
    {
        return new PersonalizationRuntimePayload(
            profile: [],
            preferences: [],
            favorites: [],
            recent: [],
            shortcuts: [],
            quickActions: [],
            onboarding: [],
            capabilities: ['personalization' => true],
            metadata: ['status' => 'warning', 'missing_tables' => $this->missingCoreTables()],
            warnings: $this->warningsForCoreTables(),
        );
    }

    public function emptyHealth(): PersonalizationHealthReport
    {
        return new PersonalizationHealthReport(
            enabled: (bool) config('heos.enterprise.personalization.enabled', true),
            healthy: false,
            status: 'warning',
            warnings: $this->warningsForCoreTables(),
            missingTables: $this->missingCoreTables(),
            statistics: $this->emptyStatistics()->toArray(),
        );
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationPermissionBridge.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class PersonalizationPermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.read');
    }

    public function canWrite(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.write');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.manage');
    }

    public function canAdmin(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'personalization.admin');
    }

    /**
     * @return array<string, bool>
     */
    public function runtimePermissions(TenantContext $context): array
    {
        return [
            'read' => $this->canRead($context),
            'write' => $this->canWrite($context),
            'manage' => $this->canManage($context),
            'admin' => $this->canAdmin($context),
        ];
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationAuditRecorder.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Models\PersonalizationActivityLog;
use Illuminate\Support\Str;

class PersonalizationAuditRecorder
{
    public function recordProfileUpdated(string $profilePublicId): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::PersonalizationProfileUpdated,
            AuditEntityType::PersonalizationProfile,
            $profilePublicId,
        );
    }

    public function recordPreferenceUpdated(string $preferencePublicId): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::PersonalizationPreferenceUpdated,
            AuditEntityType::PersonalizationPreference,
            $preferencePublicId,
        );
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function activity(
        string $organizationId,
        ?string $workspaceId,
        ?string $profileId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): void {
        if (! app(PersonalizationTableHealthSupport::class)->isTablePresent('personalization_activity_logs')) {
            return;
        }

        PersonalizationActivityLog::query()->create([
            'id' => (string) Str::uuid7(),
            'public_id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'profile_id' => $profileId,
            'action' => $action,
            'before_state' => $before,
            'after_state' => $after,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationSearchIndexer.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

class PersonalizationSearchIndexer
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function indexBestEffort(string $organizationId, ?string $workspaceId, array $payload): void
    {
        // Intentionally no-op until search subscriptions are introduced.
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationPlatformEventBridge.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

class PersonalizationPlatformEventBridge
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchBestEffort(string $event, array $payload = []): void
    {
        // Intentionally no-op until downstream listeners subscribe.
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationProfileService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationProfile;
use App\Modules\Sdk\Personalization\Data\PersonalizationProfile as PersonalizationProfileDto;
use App\Modules\Sdk\Personalization\Enums\PersonalizationScope;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class PersonalizationProfileService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function resolveOrCreate(TenantContext $context): PersonalizationProfile
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_profiles')) {
            throw new \RuntimeException('Personalization profile table is unavailable.');
        }

        $scope = PersonalizationScope::Workspace->value;
        $query = PersonalizationProfile::query()
            ->where('organization_id', $context->organization->id)
            ->where('workspace_id', $context->workspace?->id)
            ->where('membership_id', $context->membership?->id)
            ->where('user_id', $context->user->id)
            ->where('scope', $scope);

        $model = $query->first();
        if ($model !== null) {
            return $model;
        }

        return PersonalizationProfile::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'membership_id' => $context->membership?->id,
            'user_id' => $context->user->id,
            'scope' => $scope,
            'name' => 'Default',
            'is_default' => true,
            'metadata' => [],
        ]);
    }

    public function dto(TenantContext $context): PersonalizationProfileDto
    {
        return PersonalizationMapper::profile($this->resolveOrCreate($context));
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PreferenceService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationPreference;
use App\Modules\Sdk\Personalization\Contracts\PersonalizationPreferenceStore;
use App\Modules\Sdk\Personalization\Data\Preference;
use App\Modules\Sdk\Personalization\Enums\PreferenceValueType;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class PreferenceService implements PersonalizationPreferenceStore
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PersonalizationAuditRecorder $auditRecorder,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<Preference> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_preferences')) {
            return [];
        }

        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationPreference::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->orderBy('preference_key')
            ->get()
            ->map(fn (PersonalizationPreference $model) => PersonalizationMapper::preference($model))
            ->all();
    }

    public function upsert(
        TenantContext $context,
        string $key,
        string $valueType,
        mixed $value,
        ?string $scope = null,
        array $metadata = [],
    ): Preference {
        if (! $this->tableHealthSupport->isTablePresent('personalization_preferences')) {
            throw new \RuntimeException('Personalization preferences table is unavailable.');
        }

        $allowed = array_map(static fn (PreferenceValueType $type): string => $type->value, PreferenceValueType::cases());
        if (! in_array($valueType, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported preference value type [%s].', $valueType));
        }

        $profile = $this->profileService->resolveOrCreate($context);

        $query = PersonalizationPreference::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('preference_key', $key);

        $model = $query->first();
        $encoded = PersonalizationMapper::encodePreferenceValue($valueType, ['value' => $value]);

        if ($model === null) {
            $model = PersonalizationPreference::query()->create(array_merge([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'application_id' => null,
                'membership_id' => $context->membership?->id,
                'user_id' => $context->user->id,
                'profile_id' => $profile->id,
                'scope' => $scope ?? $profile->scope,
                'preference_key' => $key,
                'value_type' => $valueType,
                'metadata' => $metadata,
            ], $encoded));
        } else {
            $before = $model->toArray();
            $model->fill(array_merge([
                'scope' => $scope ?? $model->scope,
                'value_type' => $valueType,
                'value_string' => null,
                'value_boolean' => null,
                'value_integer' => null,
                'value_decimal' => null,
                'value_json' => null,
                'metadata' => $metadata !== [] ? $metadata : (is_array($model->metadata) ? $model->metadata : []),
            ], $encoded));
            $model->save();
            $this->auditRecorder->activity(
                $context->organization->id,
                $context->workspace?->id,
                $profile->id,
                'personalization.preference.updated',
                $before,
                $model->toArray(),
                $context->user->id,
                $context->membership?->id,
            );
        }

        $dto = PersonalizationMapper::preference($model->fresh());
        $this->auditRecorder->recordPreferenceUpdated($dto->publicId);

        return $dto;
    }

    public function delete(TenantContext $context, string $key): void
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_preferences')) {
            return;
        }

        $profile = $this->profileService->resolveOrCreate($context);
        PersonalizationPreference::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('preference_key', $key)
            ->delete();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/FavoriteService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationFavorite;
use App\Modules\Sdk\Personalization\Contracts\PersonalizationFavoriteStore;
use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class FavoriteService implements PersonalizationFavoriteStore
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<FavoriteItem> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            return [];
        }

        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationFavorite::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get()
            ->map(fn (PersonalizationFavorite $model) => PersonalizationMapper::favorite($model))
            ->all();
    }

    public function add(TenantContext $context, string $subjectType, string $subjectPublicId, ?string $label = null, array $metadata = []): FavoriteItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            throw new \RuntimeException('Personalization favorites table is unavailable.');
        }

        $profile = $this->profileService->resolveOrCreate($context);
        $position = (int) PersonalizationFavorite::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->max('position') + 1;

        $model = PersonalizationFavorite::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'profile_id' => $profile->id,
            'application_id' => null,
            'membership_id' => $context->membership?->id,
            'user_id' => $context->user->id,
            'scope' => $profile->scope,
            'subject_type' => $subjectType,
            'subject_public_id' => $subjectPublicId,
            'label' => $label,
            'position' => $position,
            'metadata' => $metadata,
        ]);

        return PersonalizationMapper::favorite($model);
    }

    public function remove(TenantContext $context, string $favoritePublicId): void
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            return;
        }

        $profile = $this->profileService->resolveOrCreate($context);
        PersonalizationFavorite::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('public_id', $favoritePublicId)
            ->delete();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/RecentActivityService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationRecentItem;
use App\Modules\Sdk\Personalization\Data\RecentItem;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class RecentActivityService
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function record(TenantContext $context, string $subjectType, string $subjectPublicId, ?string $title = null, array $metadata = []): RecentItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_recent_items')) {
            throw new \RuntimeException('Personalization recent table is unavailable.');
        }

        $profile = $this->profileService->resolveOrCreate($context);
        $existing = PersonalizationRecentItem::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('subject_type', $subjectType)
            ->where('subject_public_id', $subjectPublicId)
            ->first();

        if ($existing !== null) {
            $existing->fill([
                'title' => $title ?? $existing->title,
                'visited_at' => now(),
                'metadata' => $metadata !== [] ? $metadata : (is_array($existing->metadata) ? $existing->metadata : []),
            ]);
            $existing->save();

            return PersonalizationMapper::recent($existing->fresh());
        }

        $created = PersonalizationRecentItem::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'profile_id' => $profile->id,
            'membership_id' => $context->membership?->id,
            'user_id' => $context->user->id,
            'scope' => $profile->scope,
            'subject_type' => $subjectType,
            'subject_public_id' => $subjectPublicId,
            'title' => $title,
            'visited_at' => now(),
            'metadata' => $metadata,
        ]);

        return PersonalizationMapper::recent($created);
    }

    /** @return list<RecentItem> */
    public function list(TenantContext $context, int $limit = 30): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_recent_items')) {
            return [];
        }

        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationRecentItem::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->orderByDesc('visited_at')
            ->limit($limit)
            ->get()
            ->map(fn (PersonalizationRecentItem $model) => PersonalizationMapper::recent($model))
            ->all();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/ShortcutService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationShortcut;
use App\Modules\Sdk\Personalization\Data\Shortcut;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ShortcutService
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<Shortcut> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            return [];
        }

        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationShortcut::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->orderBy('position')
            ->get()
            ->map(fn (PersonalizationShortcut $model) => PersonalizationMapper::shortcut($model))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(TenantContext $context, array $data): Shortcut
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_shortcuts')) {
            throw new \RuntimeException('Personalization shortcuts table is unavailable.');
        }

        $profile = $this->profileService->resolveOrCreate($context);
        $position = (int) PersonalizationShortcut::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->max('position') + 1;

        $created = PersonalizationShortcut::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'profile_id' => $profile->id,
            'membership_id' => $context->membership?->id,
            'user_id' => $context->user->id,
            'scope' => $profile->scope,
            'shortcut_key' => (string) ($data['shortcut_key'] ?? Str::slug((string) ($data['label'] ?? 'shortcut'))),
            'label' => (string) ($data['label'] ?? 'Shortcut'),
            'icon' => isset($data['icon']) ? (string) $data['icon'] : null,
            'route' => isset($data['route']) ? (string) $data['route'] : null,
            'target' => isset($data['target']) ? (string) $data['target'] : null,
            'position' => isset($data['position']) ? (int) $data['position'] : $position,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'metadata' => is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        ]);

        return PersonalizationMapper::shortcut($created);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TenantContext $context, string $shortcutPublicId, array $data): Shortcut
    {
        $profile = $this->profileService->resolveOrCreate($context);
        $shortcut = PersonalizationShortcut::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('public_id', $shortcutPublicId)
            ->firstOrFail();

        $shortcut->fill([
            'shortcut_key' => isset($data['shortcut_key']) ? (string) $data['shortcut_key'] : $shortcut->shortcut_key,
            'label' => isset($data['label']) ? (string) $data['label'] : $shortcut->label,
            'icon' => array_key_exists('icon', $data) ? (is_string($data['icon']) ? $data['icon'] : null) : $shortcut->icon,
            'route' => array_key_exists('route', $data) ? (is_string($data['route']) ? $data['route'] : null) : $shortcut->route,
            'target' => array_key_exists('target', $data) ? (is_string($data['target']) ? $data['target'] : null) : $shortcut->target,
            'position' => isset($data['position']) ? (int) $data['position'] : $shortcut->position,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $shortcut->is_active,
            'metadata' => is_array($data['metadata'] ?? null) ? $data['metadata'] : (is_array($shortcut->metadata) ? $shortcut->metadata : []),
        ]);
        $shortcut->save();

        return PersonalizationMapper::shortcut($shortcut->fresh());
    }

    public function delete(TenantContext $context, string $shortcutPublicId): void
    {
        $profile = $this->profileService->resolveOrCreate($context);
        PersonalizationShortcut::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('public_id', $shortcutPublicId)
            ->delete();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/QuickActionService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class QuickActionService
{
    /**
     * Runtime metadata only; no execution.
     *
     * @return list<array<string, mixed>>
     */
    public function list(TenantContext $context): array
    {
        return [
            [
                'key' => 'open.notifications',
                'label' => 'Open Notifications',
                'icon' => 'bell',
                'route' => '/notifications',
            ],
            [
                'key' => 'open.dashboard',
                'label' => 'Open Dashboard',
                'icon' => 'layout',
                'route' => '/dashboard',
            ],
        ];
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/OnboardingService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationOnboardingState;
use App\Modules\Sdk\Personalization\Data\OnboardingState;
use App\Modules\Sdk\Personalization\Enums\OnboardingStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function start(TenantContext $context, string $flowKey): OnboardingState
    {
        return $this->upsert($context, $flowKey, [
            'status' => OnboardingStatus::Started->value,
            'current_step' => 'start',
        ]);
    }

    public function step(TenantContext $context, string $flowKey, string $step): OnboardingState
    {
        $state = $this->resolveStateModel($context, $flowKey);
        $completed = is_array($state->completed_steps) ? $state->completed_steps : [];
        if (! in_array($step, $completed, true)) {
            $completed[] = $step;
        }

        return $this->upsert($context, $flowKey, [
            'status' => OnboardingStatus::InProgress->value,
            'current_step' => $step,
            'completed_steps' => $completed,
        ]);
    }

    public function complete(TenantContext $context, string $flowKey): OnboardingState
    {
        return $this->upsert($context, $flowKey, [
            'status' => OnboardingStatus::Completed->value,
            'completed_at' => now(),
            'current_step' => null,
        ]);
    }

    public function reset(TenantContext $context, string $flowKey): OnboardingState
    {
        return $this->upsert($context, $flowKey, [
            'status' => OnboardingStatus::Started->value,
            'current_step' => 'start',
            'completed_steps' => [],
            'dismissed_tips' => [],
            'completed_at' => null,
        ]);
    }

    /** @return list<OnboardingState> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            return [];
        }

        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationOnboardingState::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->orderBy('flow_key')
            ->get()
            ->map(fn (PersonalizationOnboardingState $model) => PersonalizationMapper::onboarding($model))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(TenantContext $context, string $flowKey, array $attributes): OnboardingState
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_onboarding_states')) {
            throw new \RuntimeException('Personalization onboarding table is unavailable.');
        }

        $profile = $this->profileService->resolveOrCreate($context);
        $state = $this->resolveStateModel($context, $flowKey);

        if ($state === null) {
            $state = PersonalizationOnboardingState::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'profile_id' => $profile->id,
                'membership_id' => $context->membership?->id,
                'user_id' => $context->user->id,
                'scope' => $profile->scope,
                'flow_key' => $flowKey,
                'current_step' => $attributes['current_step'] ?? null,
                'completed_steps' => is_array($attributes['completed_steps'] ?? null) ? $attributes['completed_steps'] : [],
                'dismissed_tips' => is_array($attributes['dismissed_tips'] ?? null) ? $attributes['dismissed_tips'] : [],
                'status' => (string) ($attributes['status'] ?? OnboardingStatus::Started->value),
                'completed_at' => $attributes['completed_at'] ?? null,
                'metadata' => [],
            ]);

            return PersonalizationMapper::onboarding($state);
        }

        $state->fill([
            'current_step' => array_key_exists('current_step', $attributes) ? $attributes['current_step'] : $state->current_step,
            'completed_steps' => is_array($attributes['completed_steps'] ?? null) ? $attributes['completed_steps'] : (is_array($state->completed_steps) ? $state->completed_steps : []),
            'dismissed_tips' => is_array($attributes['dismissed_tips'] ?? null) ? $attributes['dismissed_tips'] : (is_array($state->dismissed_tips) ? $state->dismissed_tips : []),
            'status' => (string) ($attributes['status'] ?? $state->status),
            'completed_at' => array_key_exists('completed_at', $attributes) ? $attributes['completed_at'] : $state->completed_at,
        ]);
        $state->save();

        return PersonalizationMapper::onboarding($state->fresh());
    }

    private function resolveStateModel(TenantContext $context, string $flowKey): ?PersonalizationOnboardingState
    {
        $profile = $this->profileService->resolveOrCreate($context);

        return PersonalizationOnboardingState::query()
            ->where('organization_id', $context->organization->id)
            ->where('profile_id', $profile->id)
            ->where('flow_key', $flowKey)
            ->first();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/DismissedTipService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class DismissedTipService
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {
    }

    public function dismiss(TenantContext $context, string $flowKey, string $tipKey): \App\Modules\Sdk\Personalization\Data\OnboardingState
    {
        $states = $this->onboardingService->list($context);
        $state = collect($states)->first(fn ($item) => $item->flowKey === $flowKey);
        $dismissed = is_array($state?->dismissedTips ?? null) ? $state->dismissedTips : [];
        if (! in_array($tipKey, $dismissed, true)) {
            $dismissed[] = $tipKey;
        }

        return (new OnboardingService(
            app(PersonalizationProfileService::class),
            app(PersonalizationTableHealthSupport::class),
        ))->step($context, $flowKey, (string) ($state?->currentStep ?? 'start'));
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationStatisticsService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Models\Organization;
use App\Models\PersonalizationFavorite;
use App\Models\PersonalizationOnboardingState;
use App\Models\PersonalizationPreference;
use App\Models\PersonalizationProfile;
use App\Models\PersonalizationRecentItem;
use App\Models\PersonalizationShortcut;
use App\Models\Workspace;
use App\Modules\Sdk\Personalization\Data\PersonalizationStatistics;

class PersonalizationStatisticsService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function statisticsForScope(Organization $organization, ?Workspace $workspace): PersonalizationStatistics
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyStatistics();
        }

        $workspaceId = $workspace?->id;

        $profiles = PersonalizationProfile::query()->where('organization_id', $organization->id);
        $preferences = PersonalizationPreference::query()->where('organization_id', $organization->id);
        $favorites = PersonalizationFavorite::query()->where('organization_id', $organization->id);
        $recent = PersonalizationRecentItem::query()->where('organization_id', $organization->id);
        $shortcuts = PersonalizationShortcut::query()->where('organization_id', $organization->id);
        $onboarding = PersonalizationOnboardingState::query()->where('organization_id', $organization->id);

        if ($workspaceId !== null) {
            foreach ([$profiles, $preferences, $favorites, $recent, $shortcuts, $onboarding] as $query) {
                $query->where(function ($scoped) use ($workspaceId) {
                    $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
                });
            }
        }

        return new PersonalizationStatistics(
            profiles: $profiles->count(),
            preferences: $preferences->count(),
            favorites: $favorites->count(),
            recentItems: $recent->count(),
            shortcuts: $shortcuts->count(),
            onboardingStates: $onboarding->count(),
        );
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationHealthService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Support\Tenant\TenantContext;

class PersonalizationHealthService
{
    public function __construct(
        private readonly PersonalizationStatisticsService $statisticsService,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function health(?TenantContext $context = null): PersonalizationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.personalization.enabled', true);
        $missing = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();
        if (! $enabled) {
            $warnings[] = 'Personalization framework is disabled.';
        }

        $stats = $context !== null
            ? $this->statisticsService->statisticsForScope($context->organization, $context->workspace)
            : $this->tableHealthSupport->emptyStatistics();

        return new PersonalizationHealthReport(
            enabled: $enabled,
            healthy: $enabled && $missing === [],
            status: $missing === [] ? 'ok' : 'warning',
            warnings: $warnings,
            missingTables: $missing,
            statistics: $stats->toArray(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(): array
    {
        return $this->health()->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(TenantContext $context): array
    {
        return $this->health($context)->toArray();
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationRuntimeComposerService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Contracts\PersonalizationRuntimeComposer;
use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Support\Tenant\TenantContext;

class PersonalizationRuntimeComposerService implements PersonalizationRuntimeComposer
{
    public function __construct(
        private readonly PersonalizationProfileService $profileService,
        private readonly PreferenceService $preferenceService,
        private readonly FavoriteService $favoriteService,
        private readonly RecentActivityService $recentActivityService,
        private readonly ShortcutService $shortcutService,
        private readonly QuickActionService $quickActionService,
        private readonly OnboardingService $onboardingService,
        private readonly PersonalizationPermissionBridge $permissionBridge,
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function compose(TenantContext $context): PersonalizationRuntimePayload
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRuntime();
        }

        return new PersonalizationRuntimePayload(
            profile: $this->profileService->dto($context)->toArray(),
            preferences: array_map(fn ($item) => $item->toArray(), $this->preferenceService->list($context)),
            favorites: array_map(fn ($item) => $item->toArray(), $this->favoriteService->list($context)),
            recent: array_map(fn ($item) => $item->toArray(), $this->recentActivityService->list($context)),
            shortcuts: array_map(fn ($item) => $item->toArray(), $this->shortcutService->list($context)),
            quickActions: $this->quickActionService->list($context),
            onboarding: array_map(fn ($item) => $item->toArray(), $this->onboardingService->list($context)),
            capabilities: [
                'personalization' => true,
                'precedence' => ['global', 'organization', 'application', 'workspace', 'membership', 'user'],
            ],
            metadata: [
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'membership_public_id' => $context->membershipPublicId,
                'permissions' => $this->permissionBridge->runtimePermissions($context),
                'source' => 'personalization_framework',
            ],
            warnings: [],
        );
    }
}
PHP);

writeFile($base.'/app/Services/Personalization/PersonalizationDevelopmentService.php', <<<'PHP'
<?php

namespace App\Services\Personalization;

use App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport;
use App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload;
use App\Modules\Sdk\Personalization\Data\PersonalizationStatistics;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PersonalizationDevelopmentService
{
    public function __construct(
        private readonly PreferenceService $preferenceService,
        private readonly FavoriteService $favoriteService,
        private readonly RecentActivityService $recentActivityService,
        private readonly ShortcutService $shortcutService,
        private readonly QuickActionService $quickActionService,
        private readonly OnboardingService $onboardingService,
        private readonly PersonalizationRuntimeComposerService $runtimeComposerService,
        private readonly PersonalizationStatisticsService $statisticsService,
        private readonly PersonalizationHealthService $healthService,
        private readonly PersonalizationPermissionBridge $permissionBridge,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function runtime(TenantContext $context): PersonalizationRuntimePayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->runtimeComposerService->compose($context);
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\Preference> */
    public function listPreferences(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->preferenceService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function patchPreferences(TenantContext $context, array $payload): array
    {
        $this->requireCapability($context);
        $this->assertWrite($context);
        $updated = [];

        foreach ($payload as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'decimal',
                is_array($value) && array_is_list($value) => 'list',
                is_array($value) => 'map',
                default => 'string',
            };
            $updated[] = $this->preferenceService->upsert($context, (string) $key, $type, $value);
        }

        return $updated;
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\FavoriteItem> */
    public function listFavorites(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->favoriteService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addFavorite(TenantContext $context, array $payload): \App\Modules\Sdk\Personalization\Data\FavoriteItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->favoriteService->add(
            $context,
            (string) ($payload['subject_type'] ?? 'unknown'),
            (string) ($payload['subject_public_id'] ?? ''),
            isset($payload['label']) ? (string) $payload['label'] : null,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    public function removeFavorite(TenantContext $context, string $favoritePublicId): void
    {
        $this->requireCapability($context);
        $this->assertWrite($context);
        $this->favoriteService->remove($context, $favoritePublicId);
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\RecentItem> */
    public function listRecent(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->recentActivityService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordRecent(TenantContext $context, array $payload): \App\Modules\Sdk\Personalization\Data\RecentItem
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->recentActivityService->record(
            $context,
            (string) ($payload['subject_type'] ?? 'unknown'),
            (string) ($payload['subject_public_id'] ?? ''),
            isset($payload['title']) ? (string) $payload['title'] : null,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\Shortcut> */
    public function listShortcuts(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->shortcutService->list($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createShortcut(TenantContext $context, array $payload): \App\Modules\Sdk\Personalization\Data\Shortcut
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->shortcutService->create($context, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateShortcut(TenantContext $context, string $shortcutPublicId, array $payload): \App\Modules\Sdk\Personalization\Data\Shortcut
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->shortcutService->update($context, $shortcutPublicId, $payload);
    }

    public function deleteShortcut(TenantContext $context, string $shortcutPublicId): void
    {
        $this->requireCapability($context);
        $this->assertWrite($context);
        $this->shortcutService->delete($context, $shortcutPublicId);
    }

    /** @return list<array<string, mixed>> */
    public function quickActions(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->quickActionService->list($context);
    }

    /** @return list<\App\Modules\Sdk\Personalization\Data\OnboardingState> */
    public function onboarding(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->onboardingService->list($context);
    }

    public function onboardingStart(TenantContext $context, string $flowKey): \App\Modules\Sdk\Personalization\Data\OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->start($context, $flowKey);
    }

    public function onboardingStep(TenantContext $context, string $flowKey, string $step): \App\Modules\Sdk\Personalization\Data\OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->step($context, $flowKey, $step);
    }

    public function onboardingComplete(TenantContext $context, string $flowKey): \App\Modules\Sdk\Personalization\Data\OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->complete($context, $flowKey);
    }

    public function onboardingReset(TenantContext $context, string $flowKey): \App\Modules\Sdk\Personalization\Data\OnboardingState
    {
        $this->requireCapability($context);
        $this->assertWrite($context);

        return $this->onboardingService->reset($context, $flowKey);
    }

    public function statistics(TenantContext $context): PersonalizationStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    public function health(TenantContext $context): PersonalizationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.personalization.enabled', true)) {
            throw new HttpException(503, 'Personalization framework is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'personalization');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read personalization.');
        }
    }

    private function assertWrite(TenantContext $context): void
    {
        if (! $this->permissionBridge->canWrite($context) && ! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to write personalization.');
        }
    }
}
PHP);

$bridgeFiles = [
    'PersonalizationApplicationBridge' => 'runtimePersonalization',
    'PersonalizationUiBridge' => 'decorateRuntimePayload',
    'PersonalizationNavigationBridge' => 'decorateRuntimePayload',
    'PersonalizationThemeBridge' => 'decorateRuntimePayload',
    'PersonalizationDashboardBridge' => 'decorateRuntimePayload',
    'PersonalizationTableBridge' => 'decorateRuntimePayload',
    'PersonalizationNotificationBridge' => 'decorateRuntimePayload',
];
foreach ($bridgeFiles as $class => $method) {
    writeFile($base.'/app/Services/Personalization/'.$class.'.php', <<<PHP
<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class {$class}
{
    /**
     * @param  array<string, mixed>  \$payload
     * @return array<string, mixed>
     */
    public function {$method}(TenantContext \$context, array \$payload = []): array
    {
        try {
            \$runtime = app(PersonalizationRuntimeComposerService::class)->compose(\$context)->toArray();
            \$payload['personalization'] = \$runtime;
        } catch (\\Throwable) {
            \$payload['personalization'] = app(PersonalizationTableHealthSupport::class)->emptyRuntime()->toArray();
        }

        return \$payload;
    }
}
PHP
    );
}

writeFile($base.'/app/Http/Resources/PersonalizationRuntimeResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalizationRuntimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['data' => is_array($this->resource) ? $this->resource : $this->resource->toArray()];
    }
}
PHP);

$resources = [
    'PreferenceResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\Preference',
    'FavoriteResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\FavoriteItem',
    'RecentItemResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\RecentItem',
    'ShortcutResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\Shortcut',
    'OnboardingStateResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\OnboardingState',
    'PersonalizationStatisticsResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\PersonalizationStatistics',
    'PersonalizationHealthResource' => '\\App\\Modules\\Sdk\\Personalization\\Data\\PersonalizationHealthReport',
];
foreach ($resources as $resource => $mixin) {
    writeFile($base.'/app/Http/Resources/'.$resource.'.php', <<<PHP
<?php

namespace App\\Http\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

/** @mixin {$mixin} */
class {$resource} extends JsonResource
{
    public function toArray(Request \$request): array
    {
        if (method_exists(\$this->resource, 'toArray')) {
            return \$this->resource->toArray();
        }

        return (array) \$this->resource;
    }
}
PHP
    );
}

writeFile($base.'/app/Policies/PersonalizationProfilePolicy.php', <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Personalization\PersonalizationPermissionBridge;
use App\Support\Tenant\TenantContext;

class PersonalizationProfilePolicy
{
    public function __construct(
        private readonly PersonalizationPermissionBridge $permissionBridge,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canRead($context));
    }

    public function view(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canRead($context));
    }

    public function create(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canWrite($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canWrite($context));
    }

    public function manage(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    private function resolve(User $user, callable $callback): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $context = app(TenantContext::class);
        if ($context->user->id !== $user->id) {
            return false;
        }

        return (bool) $callback($context);
    }
}
PHP);

writeFile($base.'/app/Http/Controllers/Api/V1/Tenant/EnterprisePersonalizationController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\OnboardingStateResource;
use App\Http\Resources\PersonalizationHealthResource;
use App\Http\Resources\PersonalizationRuntimeResource;
use App\Http\Resources\PersonalizationStatisticsResource;
use App\Http\Resources\PreferenceResource;
use App\Http\Resources\RecentItemResource;
use App\Http\Resources\ShortcutResource;
use App\Models\PersonalizationProfile;
use App\Services\Personalization\PersonalizationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterprisePersonalizationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly PersonalizationDevelopmentService $developmentService) {}

    public function runtime(): PersonalizationRuntimeResource
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return new PersonalizationRuntimeResource(
            $this->developmentService->runtime(app(TenantContext::class))->toArray(),
        );
    }

    public function health(): PersonalizationHealthResource
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return new PersonalizationHealthResource($this->developmentService->health(app(TenantContext::class)));
    }

    public function statistics(): PersonalizationStatisticsResource
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return new PersonalizationStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }

    public function indexPreferences(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return PreferenceResource::collection($this->developmentService->listPreferences(app(TenantContext::class)));
    }

    public function patchPreferences(Request $request): AnonymousResourceCollection
    {
        $this->authorize('update', PersonalizationProfile::class);
        $validated = $request->validate(['preferences' => ['required', 'array']]);

        return PreferenceResource::collection(
            $this->developmentService->patchPreferences(app(TenantContext::class), $validated['preferences']),
        );
    }

    public function indexFavorites(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return FavoriteResource::collection($this->developmentService->listFavorites(app(TenantContext::class)));
    }

    public function storeFavorite(Request $request): JsonResponse
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'max:64'],
            'subject_public_id' => ['required', 'string', 'max:191'],
            'label' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
        ]);

        $favorite = $this->developmentService->addFavorite(app(TenantContext::class), $validated);

        return (new FavoriteResource($favorite))->response()->setStatusCode(201);
    }

    public function destroyFavorite(string $favoritePublicId): JsonResponse
    {
        $this->authorize('update', PersonalizationProfile::class);
        $this->developmentService->removeFavorite(app(TenantContext::class), $favoritePublicId);

        return response()->json(['deleted' => true]);
    }

    public function indexRecent(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return RecentItemResource::collection($this->developmentService->listRecent(app(TenantContext::class)));
    }

    public function storeRecent(Request $request): JsonResponse
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'max:64'],
            'subject_public_id' => ['required', 'string', 'max:191'],
            'title' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
        ]);

        $recent = $this->developmentService->recordRecent(app(TenantContext::class), $validated);

        return (new RecentItemResource($recent))->response()->setStatusCode(201);
    }

    public function indexShortcuts(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return ShortcutResource::collection($this->developmentService->listShortcuts(app(TenantContext::class)));
    }

    public function storeShortcut(Request $request): JsonResponse
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate([
            'shortcut_key' => ['nullable', 'string', 'max:128'],
            'label' => ['required', 'string', 'max:191'],
            'icon' => ['nullable', 'string', 'max:64'],
            'route' => ['nullable', 'string', 'max:191'],
            'target' => ['nullable', 'string', 'max:191'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $shortcut = $this->developmentService->createShortcut(app(TenantContext::class), $validated);

        return (new ShortcutResource($shortcut))->response()->setStatusCode(201);
    }

    public function patchShortcut(Request $request, string $shortcutPublicId): ShortcutResource
    {
        $this->authorize('update', PersonalizationProfile::class);
        $validated = $request->validate([
            'shortcut_key' => ['sometimes', 'nullable', 'string', 'max:128'],
            'label' => ['sometimes', 'string', 'max:191'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:64'],
            'route' => ['sometimes', 'nullable', 'string', 'max:191'],
            'target' => ['sometimes', 'nullable', 'string', 'max:191'],
            'position' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ]);

        return new ShortcutResource(
            $this->developmentService->updateShortcut(app(TenantContext::class), $shortcutPublicId, $validated),
        );
    }

    public function destroyShortcut(string $shortcutPublicId): JsonResponse
    {
        $this->authorize('update', PersonalizationProfile::class);
        $this->developmentService->deleteShortcut(app(TenantContext::class), $shortcutPublicId);

        return response()->json(['deleted' => true]);
    }

    public function onboardingIndex(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PersonalizationProfile::class);

        return OnboardingStateResource::collection($this->developmentService->onboarding(app(TenantContext::class)));
    }

    public function onboardingStart(Request $request): OnboardingStateResource
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate(['flow_key' => ['required', 'string', 'max:128']]);

        return new OnboardingStateResource(
            $this->developmentService->onboardingStart(app(TenantContext::class), $validated['flow_key']),
        );
    }

    public function onboardingStep(Request $request): OnboardingStateResource
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate([
            'flow_key' => ['required', 'string', 'max:128'],
            'step' => ['required', 'string', 'max:128'],
        ]);

        return new OnboardingStateResource(
            $this->developmentService->onboardingStep(app(TenantContext::class), $validated['flow_key'], $validated['step']),
        );
    }

    public function onboardingComplete(Request $request): OnboardingStateResource
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate(['flow_key' => ['required', 'string', 'max:128']]);

        return new OnboardingStateResource(
            $this->developmentService->onboardingComplete(app(TenantContext::class), $validated['flow_key']),
        );
    }

    public function onboardingReset(Request $request): OnboardingStateResource
    {
        $this->authorize('create', PersonalizationProfile::class);
        $validated = $request->validate(['flow_key' => ['required', 'string', 'max:128']]);

        return new OnboardingStateResource(
            $this->developmentService->onboardingReset(app(TenantContext::class), $validated['flow_key']),
        );
    }
}
PHP);

echo "Generated M7 personalization framework scaffold.\n";
