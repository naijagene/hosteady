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
        } elseif ($type === 'bool') {
            $from[] = "            {$camel}: (bool) (\$data['{$snake}'] ?? \$data['{$name}'] ?? false),";
        } elseif (str_starts_with($type, '?')) {
            $from[] = "            {$camel}: isset(\$data['{$snake}']) ? (string) \$data['{$snake}'] : (isset(\$data['{$name}']) ? (string) \$data['{$name}'] : null),";
        } else {
            $from[] = "            {$camel}: (string) (\$data['{$snake}'] ?? \$data['{$name}'] ?? ''),";
        }
        $to[] = "            '{$snake}' => \$this->{$camel},";
    }

    $propsStr = implode(",\n", $props);
    $fromStr = implode("\n", $from);
    $toStr = implode("\n", $to);

    return <<<PHP
<?php

namespace {$namespace};

readonly class {$class} implements \\JsonSerializable
{
    public function __construct(
{$propsStr}
    ) {
    }

    public static function fromArray(array \$data): self
    {
        return new self(
{$fromStr}
        );
    }

    public function toArray(): array
    {
        return [
{$toStr}
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

function exceptionFile(string $namespace, string $class, string $parent = 'ThemeException'): string
{
    return <<<PHP
<?php

namespace {$namespace};

class {$class} extends {$parent}
{
}

PHP;
}

$sdk = $base.'/app/Modules/Sdk/Theme';
$ns = 'App\\Modules\\Sdk\\Theme';

writeFile($sdk.'/Exceptions/ThemeException.php', exceptionFile("{$ns}\\Exceptions", 'ThemeException', '\\Exception'));
foreach ([
    'ThemeNotFoundException',
    'ThemeValidationException',
    'ThemeRenderException',
    'ThemeRegistryException',
    'ThemePublishException',
] as $exception) {
    writeFile($sdk.'/Exceptions/'.$exception.'.php', exceptionFile("{$ns}\\Exceptions", $exception));
}

$enums = [
    'ThemeDefinitionStatus' => ['Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'],
    'ThemeVersionStatus' => ['Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'],
    'ThemeInheritanceMode' => ['None' => 'none', 'MergeParent' => 'merge_parent', 'OverrideParent' => 'override_parent'],
    'ThemeScope' => ['Organization' => 'organization', 'Workspace' => 'workspace', 'Application' => 'application'],
];
foreach ($enums as $class => $cases) {
    writeFile($sdk.'/Enums/'.$class.'.php', enumFile("{$ns}\\Enums", $class, $cases));
}

$dtos = [
    'ThemeDefinition' => [
        'publicId' => 'string',
        'moduleKey' => '?string',
        'themeKey' => 'string',
        'name' => 'string',
        'description' => '?string',
        'status' => 'string',
        'scope' => 'string',
        'inheritanceMode' => 'string',
        'parentThemePublicId' => '?string',
        'tokens' => 'array',
        'metadata' => 'array',
        'applicationPublicId' => '?string',
        'currentVersionPublicId' => '?string',
    ],
    'BrandProfile' => [
        'publicId' => 'string',
        'themeDefinitionPublicId' => '?string',
        'name' => 'string',
        'logoUrl' => '?string',
        'colors' => 'array',
        'typography' => 'array',
        'assets' => 'array',
        'metadata' => 'array',
    ],
    'ThemeVersion' => [
        'publicId' => 'string',
        'themeDefinitionPublicId' => 'string',
        'versionNumber' => 'int',
        'status' => 'string',
        'snapshot' => 'array',
        'changeSummary' => '?string',
        'metadata' => 'array',
        'publishedAt' => '?string',
    ],
    'ThemeRenderPayload' => [
        'definition' => 'array',
        'version' => 'array',
        'brandProfile' => 'array',
        'theme' => 'array',
        'permissions' => 'array',
        'runtimeContext' => 'array',
        'warnings' => 'array',
    ],
    'ThemeStatistics' => [
        'definitions' => 'int',
        'versions' => 'int',
        'brandProfiles' => 'int',
        'publishedDefinitions' => 'int',
        'registeredModules' => 'int',
    ],
    'ThemeHealthReport' => [
        'enabled' => 'bool',
        'healthy' => 'bool',
        'status' => 'string',
        'definitions' => 'int',
        'versions' => 'int',
        'brandProfiles' => 'int',
        'warnings' => 'array',
        'missingTables' => 'array',
        'statistics' => 'array',
    ],
];
foreach ($dtos as $class => $fields) {
    writeFile($sdk.'/Data/'.$class.'.php', dto("{$ns}\\Data", $class, $fields));
}

writeFile($sdk.'/Contracts/ThemeRegistry.php', contractFile("{$ns}\\Contracts", 'ThemeRegistry', <<<'METHODS'
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Theme\Data\ThemeDefinition $definition): \App\Modules\Sdk\Theme\Data\ThemeDefinition;

    /** @return list<\App\Modules\Sdk\Theme\Data\ThemeDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): \App\Modules\Sdk\Theme\Data\ThemeDefinition;
METHODS));
writeFile($sdk.'/Contracts/ThemeRenderer.php', contractFile("{$ns}\\Contracts", 'ThemeRenderer', <<<'METHODS'
    public function render(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $moduleKey = null, bool $previewDraft = false): \App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
METHODS));
writeFile($sdk.'/Contracts/ThemePublisher.php', contractFile("{$ns}\\Contracts", 'ThemePublisher', <<<'METHODS'
    public function publish(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $versionPublicId = null, ?string $moduleKey = null): \App\Modules\Sdk\Theme\Data\ThemeDefinition;
METHODS));
writeFile($sdk.'/Contracts/ThemeVersionManager.php', contractFile("{$ns}\\Contracts", 'ThemeVersionManager', <<<'METHODS'
    /** @return list<\App\Modules\Sdk\Theme\Data\ThemeVersion> */
    public function listVersions(\App\Support\Tenant\TenantContext $context, string $themeKey, ?string $moduleKey = null): array;
METHODS));
writeFile($sdk.'/Contracts/ThemeInheritanceResolver.php', contractFile("{$ns}\\Contracts", 'ThemeInheritanceResolver', <<<'METHODS'
    /**
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolve(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Theme\Data\ThemeDefinition $definition, ?\App\Modules\Sdk\Theme\Data\ThemeVersion $version = null): array;
METHODS));
writeFile($sdk.'/Contracts/ThemeBrandProfileProvider.php', contractFile("{$ns}\\Contracts", 'ThemeBrandProfileProvider', <<<'METHODS'
    public function get(\App\Support\Tenant\TenantContext $context, string $themeDefinitionPublicId): ?\App\Modules\Sdk\Theme\Data\BrandProfile;

    public function update(\App\Support\Tenant\TenantContext $context, string $themeDefinitionPublicId, array $profile): \App\Modules\Sdk\Theme\Data\BrandProfile;
METHODS));

writeFile($base.'/database/migrations/2026_07_04_100200_create_theme_framework_tables.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('application_id')->nullable();
            $table->string('module_key', 64)->nullable();
            $table->string('theme_key', 128);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('scope', 32)->default('workspace');
            $table->string('inheritance_mode', 32)->default('none');
            $table->uuid('parent_theme_id')->nullable();
            $table->uuid('current_version_id')->nullable();
            $table->json('tokens_json')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['organization_id', 'workspace_id', 'theme_key']);
        });

        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id')->nullable();
            $table->string('name', 255);
            $table->string('logo_url', 512)->nullable();
            $table->json('colors_json')->nullable();
            $table->json('typography_json')->nullable();
            $table->json('assets_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->nullOnDelete();
            $table->index(['organization_id', 'workspace_id', 'theme_definition_id']);
        });

        Schema::create('theme_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status', 32)->default('draft');
            $table->json('snapshot_json')->nullable();
            $table->text('change_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->uuid('published_by_user_id')->nullable();
            $table->uuid('published_by_membership_id')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->cascadeOnDelete();
            $table->index(['theme_definition_id', 'version_number']);
        });

        Schema::create('theme_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_id')->unique();
            $table->uuid('organization_id');
            $table->uuid('workspace_id')->nullable();
            $table->uuid('theme_definition_id')->nullable();
            $table->uuid('brand_profile_id')->nullable();
            $table->string('action', 128);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->uuid('actor_membership_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('theme_definition_id')->references('id')->on('theme_definitions')->nullOnDelete();
            $table->foreign('brand_profile_id')->references('id')->on('brand_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_activity_logs');
        Schema::dropIfExists('theme_versions');
        Schema::dropIfExists('brand_profiles');
        Schema::dropIfExists('theme_definitions');
    }
};

PHP);

writeFile($base.'/app/Models/ThemeDefinition.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeDefinition extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'theme_definitions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key', 'theme_key',
        'name', 'description', 'status', 'scope', 'inheritance_mode', 'parent_theme_id',
        'current_version_id', 'tokens_json', 'metadata', 'created_by_user_id', 'created_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'tokens_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function parentTheme(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_theme_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThemeVersion::class, 'theme_definition_id');
    }

    public function brandProfiles(): HasMany
    {
        return $this->hasMany(BrandProfile::class, 'theme_definition_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ThemeVersion::class, 'current_version_id');
    }
}

PHP);

writeFile($base.'/app/Models/BrandProfile.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandProfile extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'brand_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'theme_definition_id', 'name', 'logo_url',
        'colors_json', 'typography_json', 'assets_json', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'colors_json' => 'array',
            'typography_json' => 'array',
            'assets_json' => 'array',
            'metadata' => 'array',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ThemeDefinition::class, 'theme_definition_id');
    }
}

PHP);

writeFile($base.'/app/Models/ThemeVersion.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeVersion extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    protected $table = 'theme_versions';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'theme_definition_id', 'version_number',
        'status', 'snapshot_json', 'change_summary', 'metadata', 'published_at',
        'published_by_user_id', 'published_by_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ThemeDefinition::class, 'theme_definition_id');
    }
}

PHP);

writeFile($base.'/app/Models/ThemeActivityLog.php', <<<'PHP'
<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeActivityLog extends Model
{
    use HasHeosPublicId, HasUuids;

    protected $table = 'theme_activity_logs';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'organization_id', 'workspace_id', 'theme_definition_id', 'brand_profile_id',
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

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ThemeDefinition::class, 'theme_definition_id');
    }

    public function brandProfile(): BelongsTo
    {
        return $this->belongsTo(BrandProfile::class, 'brand_profile_id');
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeMapper.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\BrandProfile;
use App\Models\OrganizationMembership;
use App\Models\ThemeDefinition;
use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Data\BrandProfile as BrandProfileDto;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Data\ThemeVersion as ThemeVersionDto;
use Illuminate\Database\Eloquent\Builder;

class ThemeMapper
{
    public static function toDefinition(ThemeDefinition $model): ThemeDefinitionDto
    {
        return new ThemeDefinitionDto(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            themeKey: $model->theme_key,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            scope: $model->scope,
            inheritanceMode: $model->inheritance_mode,
            parentThemePublicId: self::resolveThemePublicId($model->parent_theme_id),
            tokens: is_array($model->tokens_json) ? $model->tokens_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            applicationPublicId: self::resolveApplicationPublicId($model->application_id),
            currentVersionPublicId: self::resolveVersionPublicId($model->current_version_id),
        );
    }

    public static function toBrandProfile(BrandProfile $model): BrandProfileDto
    {
        return new BrandProfileDto(
            publicId: $model->public_id,
            themeDefinitionPublicId: self::resolveThemePublicId($model->theme_definition_id),
            name: $model->name,
            logoUrl: $model->logo_url,
            colors: is_array($model->colors_json) ? $model->colors_json : [],
            typography: is_array($model->typography_json) ? $model->typography_json : [],
            assets: is_array($model->assets_json) ? $model->assets_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toVersion(ThemeVersion $model): ThemeVersionDto
    {
        return new ThemeVersionDto(
            publicId: $model->public_id,
            themeDefinitionPublicId: self::resolveThemePublicId($model->theme_definition_id) ?? '',
            versionNumber: (int) $model->version_number,
            status: $model->status,
            snapshot: is_array($model->snapshot_json) ? $model->snapshot_json : [],
            changeSummary: $model->change_summary,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            publishedAt: $model->published_at?->toIso8601String(),
        );
    }

    /**
     * @param  Builder<ThemeDefinition|ThemeVersion|BrandProfile>  $query
     */
    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<ThemeDefinition|ThemeVersion|BrandProfile>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function resolveApplicationId(?string $applicationPublicId): ?string
    {
        if ($applicationPublicId === null || $applicationPublicId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()->where('public_id', $applicationPublicId)->value('id');
    }

    public static function resolveThemeId(?string $themePublicId): ?string
    {
        if ($themePublicId === null || $themePublicId === '') {
            return null;
        }

        return ThemeDefinition::query()->where('public_id', $themePublicId)->value('id');
    }

    public static function resolveVersionId(?string $versionPublicId): ?string
    {
        if ($versionPublicId === null || $versionPublicId === '') {
            return null;
        }

        return ThemeVersion::query()->where('public_id', $versionPublicId)->value('id');
    }

    public static function resolveMembershipPublicId(?string $membershipId): ?string
    {
        if ($membershipId === null || $membershipId === '') {
            return null;
        }

        return OrganizationMembership::query()->where('id', $membershipId)->value('public_id');
    }

    private static function resolveApplicationPublicId(?string $applicationId): ?string
    {
        if ($applicationId === null || $applicationId === '') {
            return null;
        }

        return ApplicationRuntimeApp::query()->where('id', $applicationId)->value('public_id');
    }

    private static function resolveThemePublicId(?string $themeId): ?string
    {
        if ($themeId === null || $themeId === '') {
            return null;
        }

        return ThemeDefinition::query()->where('id', $themeId)->value('public_id');
    }

    private static function resolveVersionPublicId(?string $versionId): ?string
    {
        if ($versionId === null || $versionId === '') {
            return null;
        }

        return ThemeVersion::query()->where('id', $versionId)->value('public_id');
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeTableHealthSupport.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class ThemeTableHealthSupport
{
    /** @var list<string> */
    public const CORE_TABLES = [
        'theme_definitions',
        'brand_profiles',
        'theme_versions',
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

    public function emptyStatistics(): ThemeStatistics
    {
        return new ThemeStatistics(0, 0, 0, 0, 0);
    }

    /**
     * @param  array<string, bool>  $permissions
     */
    public function emptyRenderPayload(
        TenantContext $context,
        array $permissions,
        ?string $moduleKey = null,
        ?string $themeKey = null,
    ): ThemeRenderPayload {
        return new ThemeRenderPayload(
            definition: [],
            version: [],
            brandProfile: [],
            theme: [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ],
            permissions: $permissions,
            runtimeContext: [
                'status' => 'warning',
                'missing_tables' => $this->missingCoreTables(),
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'module_key' => $moduleKey,
                'theme_key' => $themeKey,
            ],
            warnings: $this->warningsForCoreTables(),
        );
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemePermissionBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class ThemePermissionBridge
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.manage');
    }

    public function canPublish(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.publish');
    }

    public function canManageBrand(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'themes.brand');
    }

    /**
     * @return array<string, bool>
     */
    public function renderPermissions(TenantContext $context): array
    {
        return [
            'read' => $this->canRead($context),
            'manage' => $this->canManage($context),
            'publish' => $this->canPublish($context),
            'brand' => $this->canManageBrand($context),
        ];
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeAuditRecorder.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Models\ThemeActivityLog;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ThemeAuditRecorder
{
    public function recordDefinitionRegistered(ThemeDefinition $definition): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::ThemeDefinitionRegistered,
            AuditEntityType::ThemeDefinition,
            $definition->publicId,
            ['theme_key' => $definition->themeKey],
        );
    }

    public function recordDefinitionUpdated(ThemeDefinition $definition): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::ThemeDefinitionUpdated,
            AuditEntityType::ThemeDefinition,
            $definition->publicId,
            ['theme_key' => $definition->themeKey],
        );
    }

    public function recordBrandProfileUpdated(string $brandProfilePublicId): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::ThemeBrandProfileUpdated,
            AuditEntityType::BrandProfile,
            $brandProfilePublicId,
        );
    }

    public function recordPublished(ThemeDefinition $definition): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::ThemePublished,
            AuditEntityType::ThemeDefinition,
            $definition->publicId,
            ['theme_key' => $definition->themeKey],
        );
    }

    public function recordRendered(string $themeDefinitionPublicId, TenantContext $context): void
    {
        app(\App\Services\Audit\DomainAuditRecorder::class)->record(
            AuditAction::ThemeRendered,
            AuditEntityType::ThemeDefinition,
            $themeDefinitionPublicId,
            ['workspace_public_id' => $context->workspacePublicId],
        );
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordActivity(
        string $organizationId,
        ?string $workspaceId,
        ?string $themeDefinitionId,
        ?string $brandProfileId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): void {
        if (! app(ThemeTableHealthSupport::class)->isTablePresent('theme_activity_logs')) {
            return;
        }

        ThemeActivityLog::query()->create([
            'id' => (string) Str::uuid7(),
            'public_id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'theme_definition_id' => $themeDefinitionId,
            'brand_profile_id' => $brandProfileId,
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

writeFile($base.'/app/Services/Theme/ThemeSearchIndexer.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;

class ThemeSearchIndexer
{
    public function indexDefinitionBestEffort(ThemeDefinition $definition, string $organizationId, ?string $workspaceId): void
    {
        // Theme framework currently stores metadata only; no external indexing.
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemePlatformEventBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

class ThemePlatformEventBridge
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchBestEffort(string $event, array $payload = []): void
    {
        // Intentionally no-op until theme events are subscribed by downstream modules.
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeDefaultGeneratorService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

class ThemeDefaultGeneratorService
{
    /**
     * @return array<string, mixed>
     */
    public static function safeDefaultTokens(): array
    {
        return [
            'color.primary' => '#2563eb',
            'color.surface' => '#ffffff',
            'color.text' => '#111827',
            'spacing.unit' => '0.25rem',
            'radius.base' => '0.375rem',
        ];
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeRegistryService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\ThemeDefinition;
use App\Modules\Sdk\Theme\Contracts\ThemeRegistry;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode;
use App\Modules\Sdk\Theme\Enums\ThemeScope;
use App\Modules\Sdk\Theme\Exceptions\ThemeNotFoundException;
use App\Modules\Sdk\Theme\Exceptions\ThemeRegistryException;
use App\Modules\Sdk\Theme\Exceptions\ThemeValidationException;
use Illuminate\Support\Str;

class ThemeRegistryService implements ThemeRegistry
{
    public function __construct(
        private readonly ThemeAuditRecorder $auditRecorder,
        private readonly ThemeSearchIndexer $searchIndexer,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, ThemeDefinitionDto $definition): ThemeDefinitionDto
    {
        $this->assertDefinitionsTablePresent();
        $definition = $this->resolveDefinitionSource($definition);
        $this->assertNotDuplicate($organizationId, $workspaceId, $definition);

        $model = ThemeDefinition::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId ?? ThemeMapper::resolveApplicationId($definition->applicationPublicId),
            'module_key' => $definition->moduleKey,
            'theme_key' => $definition->themeKey,
            'name' => $definition->name !== '' ? $definition->name : $definition->themeKey,
            'description' => $definition->description,
            'status' => $definition->status !== '' ? $definition->status : ThemeDefinitionStatus::Draft->value,
            'scope' => $definition->scope !== '' ? $definition->scope : ThemeScope::Workspace->value,
            'inheritance_mode' => $definition->inheritanceMode !== '' ? $definition->inheritanceMode : ThemeInheritanceMode::None->value,
            'parent_theme_id' => ThemeMapper::resolveThemeId($definition->parentThemePublicId),
            'tokens_json' => $definition->tokens,
            'metadata' => $definition->metadata,
        ]);

        $created = ThemeMapper::toDefinition($model);
        $this->auditRecorder->recordDefinitionRegistered($created);
        $this->searchIndexer->indexDefinitionBestEffort($created, $organizationId, $workspaceId);

        return $created;
    }

    /** @return list<ThemeDefinitionDto> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return [];
        }

        $query = ThemeDefinition::query()
            ->orderBy('name')
            ->limit($limit);

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (ThemeDefinition $model) => ThemeMapper::toDefinition($model))->all();
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): ThemeDefinitionDto
    {
        $this->assertDefinitionsTablePresent();

        return ThemeMapper::toDefinition(
            $this->resolveModelByKey($organizationId, $workspaceId, $moduleKey, $themeKey),
        );
    }

    /**
     * @param  ThemeDefinitionDto|array<string, mixed>  $source
     */
    public function registerFromSource(string $organizationId, ?string $workspaceId, ?string $applicationId, mixed $source): ThemeDefinitionDto
    {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveDefinitionSource($source));
    }

    public function resolveModelByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): ThemeDefinition
    {
        $this->assertDefinitionsTablePresent();

        $query = ThemeDefinition::query()->where('theme_key', $themeKey);

        if ($moduleKey !== '') {
            $query->where('module_key', $moduleKey);
        }

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new ThemeNotFoundException(sprintf('Theme [%s.%s] was not found.', $moduleKey, $themeKey));
        }

        return $model;
    }

    public function resolveModelByPublicId(string $organizationId, ?string $workspaceId, string $publicId): ThemeDefinition
    {
        $this->assertDefinitionsTablePresent();

        $query = ThemeDefinition::query()->where('public_id', $publicId);
        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new ThemeNotFoundException(sprintf('Theme definition [%s] was not found.', $publicId));
        }

        return $model;
    }

    /**
     * @param  ThemeDefinitionDto|array<string, mixed>  $source
     */
    private function resolveDefinitionSource(mixed $source): ThemeDefinitionDto
    {
        if ($source instanceof ThemeDefinitionDto) {
            return $source;
        }

        if (is_array($source)) {
            return ThemeDefinitionDto::fromArray($source);
        }

        throw new ThemeRegistryException('Unsupported theme definition source.');
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, ThemeDefinitionDto $definition): void
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return;
        }

        $query = ThemeDefinition::query()->where('theme_key', $definition->themeKey);

        if ($definition->moduleKey !== null && $definition->moduleKey !== '') {
            $query->where('module_key', $definition->moduleKey);
        }

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new ThemeRegistryException(sprintf(
                'Theme [%s.%s] is already registered.',
                $definition->moduleKey ?? '',
                $definition->themeKey,
            ));
        }
    }

    private function assertDefinitionsTablePresent(): void
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            throw new ThemeValidationException('Theme definitions table is not available.');
        }
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeDefinitionService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Support\Tenant\TenantContext;

class ThemeDefinitionService
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<ThemeDefinition> */
    public function list(TenantContext $context): array
    {
        return $this->registryService->list($context->organization->id, $context->workspace?->id);
    }

    /**
     * @param  ThemeDefinition|array<string, mixed>  $definition
     */
    public function create(TenantContext $context, mixed $definition): ThemeDefinition
    {
        return $this->registryService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $definition,
        );
    }

    public function find(TenantContext $context, string $moduleKey, string $themeKey): ThemeDefinition
    {
        return $this->registryService->findByKey($context->organization->id, $context->workspace?->id, $moduleKey, $themeKey);
    }

    public function findByPublicId(TenantContext $context, string $publicId): ThemeDefinition
    {
        return ThemeMapper::toDefinition(
            $this->registryService->resolveModelByPublicId($context->organization->id, $context->workspace?->id, $publicId),
        );
    }

    public function update(TenantContext $context, ThemeDefinition $definition): ThemeDefinition
    {
        $model = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $definition->publicId,
        );

        $model->fill([
            'module_key' => $definition->moduleKey,
            'theme_key' => $definition->themeKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'status' => $definition->status,
            'scope' => $definition->scope,
            'inheritance_mode' => $definition->inheritanceMode,
            'parent_theme_id' => ThemeMapper::resolveThemeId($definition->parentThemePublicId),
            'tokens_json' => $definition->tokens,
            'metadata' => $definition->metadata,
            'application_id' => ThemeMapper::resolveApplicationId($definition->applicationPublicId),
        ]);
        $model->save();

        $updated = ThemeMapper::toDefinition($model->fresh());
        $this->auditRecorder->recordDefinitionUpdated($updated);

        return $updated;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/BrandProfileService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\BrandProfile;
use App\Modules\Sdk\Theme\Contracts\ThemeBrandProfileProvider;
use App\Modules\Sdk\Theme\Data\BrandProfile as BrandProfileDto;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class BrandProfileService implements ThemeBrandProfileProvider
{
    public function __construct(
        private readonly ThemeTableHealthSupport $tableHealthSupport,
        private readonly ThemeAuditRecorder $auditRecorder,
    ) {
    }

    public function get(TenantContext $context, string $themeDefinitionPublicId): ?BrandProfileDto
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            return null;
        }

        $themeDefinitionId = ThemeMapper::resolveThemeId($themeDefinitionPublicId);
        if ($themeDefinitionId === null) {
            return null;
        }

        $query = BrandProfile::query()->where('theme_definition_id', $themeDefinitionId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->orderByDesc('updated_at')->first();

        return $model !== null ? ThemeMapper::toBrandProfile($model) : null;
    }

    public function update(TenantContext $context, string $themeDefinitionPublicId, array $profile): BrandProfileDto
    {
        $themeDefinitionId = ThemeMapper::resolveThemeId($themeDefinitionPublicId);

        $query = BrandProfile::query()->where('theme_definition_id', $themeDefinitionId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        /** @var BrandProfile|null $existing */
        $existing = $query->first();

        if ($existing === null) {
            $existing = BrandProfile::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'theme_definition_id' => $themeDefinitionId,
                'name' => (string) ($profile['name'] ?? 'Brand profile'),
                'logo_url' => isset($profile['logo_url']) ? (string) $profile['logo_url'] : null,
                'colors_json' => is_array($profile['colors'] ?? null) ? $profile['colors'] : [],
                'typography_json' => is_array($profile['typography'] ?? null) ? $profile['typography'] : [],
                'assets_json' => is_array($profile['assets'] ?? null) ? $profile['assets'] : [],
                'metadata' => is_array($profile['metadata'] ?? null) ? $profile['metadata'] : [],
            ]);
        } else {
            $existing->fill([
                'name' => (string) ($profile['name'] ?? $existing->name),
                'logo_url' => isset($profile['logo_url']) ? (string) $profile['logo_url'] : $existing->logo_url,
                'colors_json' => is_array($profile['colors'] ?? null) ? $profile['colors'] : (is_array($existing->colors_json) ? $existing->colors_json : []),
                'typography_json' => is_array($profile['typography'] ?? null) ? $profile['typography'] : (is_array($existing->typography_json) ? $existing->typography_json : []),
                'assets_json' => is_array($profile['assets'] ?? null) ? $profile['assets'] : (is_array($existing->assets_json) ? $existing->assets_json : []),
                'metadata' => is_array($profile['metadata'] ?? null) ? $profile['metadata'] : (is_array($existing->metadata) ? $existing->metadata : []),
            ]);
            $existing->save();
        }

        $mapped = ThemeMapper::toBrandProfile($existing->fresh());
        $this->auditRecorder->recordBrandProfileUpdated($mapped->publicId);

        return $mapped;
    }

    /** @return list<BrandProfileDto> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            return [];
        }

        $query = BrandProfile::query()->orderBy('name');
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->get()->map(fn (BrandProfile $model) => ThemeMapper::toBrandProfile($model))->all();
    }

    public function findByPublicId(TenantContext $context, string $publicId): BrandProfileDto
    {
        $query = BrandProfile::query()->where('public_id', $publicId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return ThemeMapper::toBrandProfile($query->firstOrFail());
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeVersionService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Contracts\ThemeVersionManager;
use App\Modules\Sdk\Theme\Data\ThemeVersion as ThemeVersionDto;
use App\Modules\Sdk\Theme\Enums\ThemeVersionStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ThemeVersionService implements ThemeVersionManager
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<ThemeVersionDto> */
    public function listVersions(TenantContext $context, string $themeKey, ?string $moduleKey = null): array
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')) {
            return [];
        }

        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        return ThemeVersion::query()
            ->where('theme_definition_id', $definition->id)
            ->orderByDesc('version_number')
            ->get()
            ->map(fn (ThemeVersion $model) => ThemeMapper::toVersion($model))
            ->all();
    }

    public function findVersion(TenantContext $context, string $versionPublicId): ThemeVersionDto
    {
        $query = ThemeVersion::query()->where('public_id', $versionPublicId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return ThemeMapper::toVersion($query->firstOrFail());
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function createDraft(TenantContext $context, string $themeDefinitionPublicId, array $snapshot, ?string $changeSummary = null): ThemeVersionDto
    {
        $definition = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $themeDefinitionPublicId,
        );

        $nextVersion = ((int) ThemeVersion::query()->where('theme_definition_id', $definition->id)->max('version_number')) + 1;

        $created = ThemeVersion::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'theme_definition_id' => $definition->id,
            'version_number' => $nextVersion,
            'status' => ThemeVersionStatus::Draft->value,
            'snapshot_json' => $snapshot,
            'change_summary' => $changeSummary,
            'metadata' => [],
        ]);

        return ThemeMapper::toVersion($created);
    }

    public function findPublishedVersion(TenantContext $context, string $themeKey, ?string $moduleKey = null): ?ThemeVersionDto
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')) {
            return null;
        }

        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = ThemeVersion::query()
            ->where('theme_definition_id', $definition->id)
            ->where('status', ThemeVersionStatus::Published->value)
            ->orderByDesc('version_number')
            ->first();

        return $version !== null ? ThemeMapper::toVersion($version) : null;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeInheritanceResolverService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Contracts\ThemeInheritanceResolver;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeVersion;
use App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode;
use App\Support\Tenant\TenantContext;

class ThemeInheritanceResolverService implements ThemeInheritanceResolver
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    /**
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolve(TenantContext $context, ThemeDefinition $definition, ?ThemeVersion $version = null): array
    {
        $warnings = [];
        $resolved = $version?->snapshot['tokens'] ?? $definition->tokens;
        if (! is_array($resolved)) {
            $resolved = [];
        }

        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return ['theme' => $resolved, 'warnings' => $warnings];
        }

        $mode = $definition->inheritanceMode !== '' ? $definition->inheritanceMode : ThemeInheritanceMode::None->value;
        if ($mode === ThemeInheritanceMode::None->value || $definition->parentThemePublicId === null || $definition->parentThemePublicId === '') {
            return ['theme' => $resolved, 'warnings' => $warnings];
        }

        $visited = [$definition->publicId];
        $parentPublicId = $definition->parentThemePublicId;
        $parentTokens = [];

        while ($parentPublicId !== null && $parentPublicId !== '') {
            if (in_array($parentPublicId, $visited, true)) {
                $warnings[] = sprintf('Theme inheritance cycle detected at [%s].', $parentPublicId);
                break;
            }
            $visited[] = $parentPublicId;

            try {
                $parentModel = $this->registryService->resolveModelByPublicId(
                    $context->organization->id,
                    $context->workspace?->id,
                    $parentPublicId,
                );
            } catch (\Throwable) {
                $warnings[] = sprintf('Parent theme [%s] was not found.', $parentPublicId);
                break;
            }

            $parent = ThemeMapper::toDefinition($parentModel);
            $parentTokens = is_array($parent->tokens) ? $parent->tokens : [];
            $parentPublicId = $parent->parentThemePublicId;

            if ($parent->inheritanceMode === ThemeInheritanceMode::None->value) {
                break;
            }
        }

        if ($mode === ThemeInheritanceMode::MergeParent->value) {
            return ['theme' => array_replace($parentTokens, $resolved), 'warnings' => $warnings];
        }

        if ($mode === ThemeInheritanceMode::OverrideParent->value) {
            return ['theme' => $resolved !== [] ? $resolved : $parentTokens, 'warnings' => $warnings];
        }

        return ['theme' => $resolved, 'warnings' => $warnings];
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeRendererService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Contracts\ThemeRenderer;
use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Support\Tenant\TenantContext;

class ThemeRendererService implements ThemeRenderer
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeVersionService $versionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeInheritanceResolverService $inheritanceResolver,
        private readonly ThemePermissionBridge $permissionBridge,
        private readonly ThemeAuditRecorder $auditRecorder,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function render(TenantContext $context, string $themeKey, ?string $moduleKey = null, bool $previewDraft = false): ThemeRenderPayload
    {
        $permissions = $this->permissionBridge->renderPermissions($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload($context, $permissions, $moduleKey, $themeKey);
        }

        $definition = $this->registryService->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = $this->versionService->findPublishedVersion($context, $themeKey, $moduleKey);
        $brandProfile = $this->brandProfileService->get($context, $definition->publicId);
        $resolved = $this->inheritanceResolver->resolve($context, $definition, $version);

        $theme = [
            'tokens' => $resolved['theme'] !== [] ? $resolved['theme'] : ThemeDefaultGeneratorService::safeDefaultTokens(),
            'brand' => $brandProfile?->toArray() ?? [],
            'source' => $resolved['theme'] !== [] ? 'theme_designer' : 'safe_default',
        ];

        $payload = new ThemeRenderPayload(
            definition: $definition->toArray(),
            version: $version?->toArray() ?? [],
            brandProfile: $brandProfile?->toArray() ?? [],
            theme: $theme,
            permissions: $permissions,
            runtimeContext: [
                'organization_public_id' => $context->organizationPublicId,
                'workspace_public_id' => $context->workspacePublicId,
                'membership_public_id' => $context->membershipPublicId,
                'module_key' => $definition->moduleKey,
                'theme_key' => $definition->themeKey,
                'application_public_id' => $definition->applicationPublicId,
            ],
            warnings: $resolved['warnings'],
        );

        $this->auditRecorder->recordRendered($definition->publicId, $context);

        return $payload;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemePublisherService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Contracts\ThemePublisher;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Modules\Sdk\Theme\Enums\ThemeVersionStatus;
use App\Modules\Sdk\Theme\Exceptions\ThemePublishException;
use App\Support\Tenant\TenantContext;

class ThemePublisherService implements ThemePublisher
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeVersionService $versionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeAuditRecorder $auditRecorder,
    ) {
    }

    public function publish(TenantContext $context, string $themeKey, ?string $versionPublicId = null, ?string $moduleKey = null): ThemeDefinitionDto
    {
        $definitionModel = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = $versionPublicId !== null && $versionPublicId !== ''
            ? $this->versionService->findVersion($context, $versionPublicId)
            : $this->versionService->createDraft($context, $definitionModel->public_id, [
                'tokens' => is_array($definitionModel->tokens_json) ? $definitionModel->tokens_json : [],
                'brand_profile' => $this->brandProfileService->get($context, $definitionModel->public_id)?->toArray() ?? [],
                'theme_key' => $definitionModel->theme_key,
                'module_key' => $definitionModel->module_key,
            ], 'Auto snapshot publish');

        $versionId = ThemeMapper::resolveVersionId($version->publicId);
        if ($versionId === null) {
            throw new ThemePublishException('Unable to resolve theme version for publish.');
        }

        ThemeVersion::query()
            ->where('id', $versionId)
            ->update([
                'status' => ThemeVersionStatus::Published->value,
                'published_at' => now(),
                'published_by_user_id' => $context->user->id,
                'published_by_membership_id' => $context->membership->id,
            ]);

        $definitionModel->fill([
            'status' => ThemeDefinitionStatus::Published->value,
            'current_version_id' => $versionId,
        ]);
        $definitionModel->save();

        $published = ThemeMapper::toDefinition($definitionModel->fresh());
        $this->auditRecorder->recordPublished($published);

        return $published;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeStatisticsService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Models\BrandProfile;
use App\Models\ThemeDefinition;
use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Models\Organization;
use App\Models\Workspace;

class ThemeStatisticsService
{
    public function __construct(
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function statisticsForScope(Organization $organization, ?Workspace $workspace): ThemeStatistics
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyStatistics();
        }

        $definitions = ThemeDefinition::query();
        ThemeMapper::applyOrganizationScope($definitions, $organization->id);
        ThemeMapper::applyWorkspaceScope($definitions, $workspace?->id);

        $versions = ThemeVersion::query();
        ThemeMapper::applyOrganizationScope($versions, $organization->id);
        ThemeMapper::applyWorkspaceScope($versions, $workspace?->id);

        $brands = BrandProfile::query();
        ThemeMapper::applyOrganizationScope($brands, $organization->id);
        ThemeMapper::applyWorkspaceScope($brands, $workspace?->id);

        return new ThemeStatistics(
            definitions: $definitions->count(),
            versions: $versions->count(),
            brandProfiles: $brands->count(),
            publishedDefinitions: (clone $definitions)->where('status', ThemeDefinitionStatus::Published->value)->count(),
            registeredModules: (clone $definitions)->distinct('module_key')->count('module_key'),
        );
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeHealthService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use App\Support\Tenant\TenantContext;

class ThemeHealthService
{
    public function __construct(
        private readonly ThemeStatisticsService $statisticsService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function health(?TenantContext $context = null): ThemeHealthReport
    {
        $enabled = (bool) config('heos.enterprise.themes.enabled', true);
        $missingTables = $this->tableHealthSupport->missingCoreTables();
        $warnings = $this->tableHealthSupport->warningsForCoreTables();

        if (! $enabled) {
            $warnings[] = 'Theme framework is disabled.';
        }

        $stats = $context !== null
            ? $this->statisticsService->statisticsForScope($context->organization, $context->workspace)
            : $this->tableHealthSupport->emptyStatistics();

        if ($stats->definitions === 0 && $missingTables === []) {
            $warnings[] = 'No theme definitions registered.';
        }

        return new ThemeHealthReport(
            enabled: $enabled,
            healthy: $enabled && $missingTables === [],
            status: $missingTables === [] ? 'ok' : 'warning',
            definitions: $stats->definitions,
            versions: $stats->versions,
            brandProfiles: $stats->brandProfiles,
            warnings: $warnings,
            missingTables: $missingTables,
            statistics: $stats->toArray(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(): array
    {
        $health = $this->health();

        return $health->toArray();
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

writeFile($base.'/app/Services/Theme/ThemeApplicationBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeApplicationBridge
{
    /**
     * @return array<string, mixed>
     */
    public function runtimeTheme(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        try {
            return app(ThemeRendererService::class)
                ->render($context, $themeKey, $moduleKey)
                ->theme;
        } catch (\Throwable) {
            return [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ];
        }
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeNavigationBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeNavigationBridge
{
    /**
     * @param  array<string, mixed>  $navigationPayload
     * @return array<string, mixed>
     */
    public function decorateRuntimePayload(TenantContext $context, array $navigationPayload, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $navigationPayload['theme'] = app(ThemeApplicationBridge::class)->runtimeTheme($context, $themeKey, $moduleKey);

        return $navigationPayload;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeDocumentBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeDocumentBridge
{
    /**
     * @param  array<string, mixed>  $documentPayload
     * @return array<string, mixed>
     */
    public function decorateDocumentPayload(TenantContext $context, array $documentPayload, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $documentPayload['theme'] = app(ThemeApplicationBridge::class)->runtimeTheme($context, $themeKey, $moduleKey);

        return $documentPayload;
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeUiBridge.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Support\Tenant\TenantContext;

class ThemeUiBridge
{
    /**
     * @param  array<string, mixed>  $pageThemeOverride
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolveForPage(
        TenantContext $context,
        ?string $moduleKey,
        ?string $pageKey,
        array $pageThemeOverride = [],
    ): array {
        $moduleKey ??= '';
        $themeKey = $pageKey !== null && $pageKey !== '' ? $pageKey : 'default';

        try {
            $rendered = app(ThemeRendererService::class)->render($context, $themeKey, $moduleKey);
            $resolvedTheme = $rendered->theme;
            $warnings = $rendered->warnings;
        } catch (\Throwable) {
            $resolvedTheme = [
                'tokens' => ThemeDefaultGeneratorService::safeDefaultTokens(),
                'source' => 'safe_default',
            ];
            $warnings = [];
        }

        if ($pageThemeOverride !== []) {
            $resolvedTheme = array_replace_recursive($resolvedTheme, $pageThemeOverride);
            $resolvedTheme['source'] = 'theme_designer+page_override';
        }

        return [
            'theme' => $resolvedTheme,
            'warnings' => $warnings,
        ];
    }
}

PHP);

writeFile($base.'/app/Services/Theme/ThemeDevelopmentService.php', <<<'PHP'
<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\BrandProfile;
use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use App\Modules\Sdk\Theme\Data\ThemeRenderPayload;
use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use App\Modules\Sdk\Theme\Data\ThemeVersion;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ThemeDevelopmentService
{
    public function __construct(
        private readonly ThemeDefinitionService $definitionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeVersionService $versionService,
        private readonly ThemeRendererService $rendererService,
        private readonly ThemePublisherService $publisherService,
        private readonly ThemeHealthService $healthService,
        private readonly ThemeStatisticsService $statisticsService,
        private readonly ThemePermissionBridge $permissionBridge,
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<ThemeDefinition> */
    public function listDefinitions(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->list($context);
    }

    /**
     * @param  ThemeDefinition|array<string, mixed>  $definition
     */
    public function registerDefinition(TenantContext $context, mixed $definition): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->definitionService->create($context, $definition);
    }

    public function findDefinitionByPublicId(TenantContext $context, string $publicId): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->findByPublicId($context, $publicId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDefinitionByPublicId(TenantContext $context, string $publicId, array $data): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $existing = $this->definitionService->findByPublicId($context, $publicId);

        return $this->definitionService->update(
            $context,
            ThemeDefinition::fromArray(array_merge($existing->toArray(), $data)),
        );
    }

    /** @return list<BrandProfile> */
    public function listBrandProfiles(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->brandProfileService->list($context);
    }

    public function findBrandProfileByPublicId(TenantContext $context, string $publicId): BrandProfile
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->brandProfileService->findByPublicId($context, $publicId);
    }

    public function updateBrandProfile(TenantContext $context, string $themeDefinitionPublicId, array $profile): BrandProfile
    {
        $this->requireCapability($context);
        $this->assertBrand($context);

        return $this->brandProfileService->update($context, $themeDefinitionPublicId, $profile);
    }

    public function createThemeVersion(TenantContext $context, string $themeDefinitionPublicId, array $snapshot = [], ?string $changeSummary = null): ThemeVersion
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        if ($snapshot === []) {
            $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);
            $snapshot = [
                'tokens' => $definition->tokens,
                'brand_profile' => $this->brandProfileService->get($context, $themeDefinitionPublicId)?->toArray() ?? [],
            ];
        }

        return $this->versionService->createDraft($context, $themeDefinitionPublicId, $snapshot, $changeSummary);
    }

    /** @return list<ThemeVersion> */
    public function listVersionsForDefinition(TenantContext $context, string $themeDefinitionPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->versionService->listVersions($context, $definition->themeKey, $definition->moduleKey);
    }

    public function publishDefinition(TenantContext $context, string $themeDefinitionPublicId, ?string $versionPublicId = null): ThemeDefinition
    {
        $this->requireCapability($context);
        $this->assertPublish($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->publisherService->publish($context, $definition->themeKey, $versionPublicId, $definition->moduleKey);
    }

    public function renderDefinition(TenantContext $context, string $themeDefinitionPublicId): ThemeRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $themeDefinitionPublicId);

        return $this->rendererService->render($context, $definition->themeKey, $definition->moduleKey);
    }

    public function renderTheme(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): ThemeRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload(
                $context,
                $this->permissionBridge->renderPermissions($context),
                $moduleKey,
                $themeKey,
            );
        }

        try {
            return $this->rendererService->render($context, $themeKey, $moduleKey);
        } catch (\Throwable) {
            return $this->tableHealthSupport->emptyRenderPayload(
                $context,
                $this->permissionBridge->renderPermissions($context),
                $moduleKey,
                $themeKey,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function composeRuntime(TenantContext $context, string $themeKey = 'default', ?string $moduleKey = null): array
    {
        $payload = $this->renderTheme($context, $themeKey, $moduleKey);

        return [
            'definition' => $payload->definition,
            'version' => $payload->version,
            'brand_profile' => $payload->brandProfile,
            'theme' => $payload->theme,
            'runtime_context' => $payload->runtimeContext,
            'permissions' => $payload->permissions,
            'warnings' => $payload->warnings,
            'source' => 'theme_framework',
        ];
    }

    public function health(TenantContext $context): ThemeHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): ThemeStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.themes.enabled', true)) {
            throw new HttpException(503, 'Theme framework is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'themes');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read themes.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage themes.');
        }
    }

    private function assertPublish(TenantContext $context): void
    {
        if (! $this->permissionBridge->canPublish($context)) {
            throw new HttpException(403, 'You do not have permission to publish themes.');
        }
    }

    private function assertBrand(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManageBrand($context)) {
            throw new HttpException(403, 'You do not have permission to manage brand profiles.');
        }
    }
}

PHP);

writeFile($base.'/app/Http/Resources/ThemeDefinitionResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeDefinition */
class ThemeDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

PHP);

writeFile($base.'/app/Http/Resources/BrandProfileResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\BrandProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BrandProfile */
class BrandProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof BrandProfile) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

PHP);

writeFile($base.'/app/Http/Resources/ThemeVersionResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeVersion */
class ThemeVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeVersion) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

PHP);

writeFile($base.'/app/Http/Resources/ThemeRuntimeResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeRuntimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['data' => $this->resource];
    }
}

PHP);

writeFile($base.'/app/Http/Resources/ThemeStatisticsResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeStatistics */
class ThemeStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

PHP);

writeFile($base.'/app/Http/Resources/ThemeHealthResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeHealthReport */
class ThemeHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}

PHP);

writeFile($base.'/app/Policies/ThemeDefinitionPolicy.php', <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Theme\ThemePermissionBridge;
use App\Support\Tenant\TenantContext;

class ThemeDefinitionPolicy
{
    public function __construct(
        private readonly ThemePermissionBridge $permissionBridge,
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
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    public function update(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManage($context));
    }

    public function publish(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canPublish($context));
    }

    public function brand(User $user): bool
    {
        return $this->resolve($user, fn (TenantContext $context) => $this->permissionBridge->canManageBrand($context));
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

writeFile($base.'/app/Http/Controllers/Api/V1/Tenant/EnterpriseThemeController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandProfileResource;
use App\Http\Resources\ThemeDefinitionResource;
use App\Http\Resources\ThemeHealthResource;
use App\Http\Resources\ThemeRuntimeResource;
use App\Http\Resources\ThemeStatisticsResource;
use App\Http\Resources\ThemeVersionResource;
use App\Models\ThemeDefinition;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Services\Theme\ThemeDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseThemeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ThemeDevelopmentService $developmentService) {}

    public function indexThemes(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return ThemeDefinitionResource::collection(
            $this->developmentService->listDefinitions(app(TenantContext::class)),
        );
    }

    public function storeTheme(Request $request): JsonResponse
    {
        $this->authorize('create', ThemeDefinition::class);
        $validated = $request->validate([
            'module_key' => ['nullable', 'string', 'max:64'],
            'theme_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'inheritance_mode' => ['nullable', 'string'],
            'parent_theme_public_id' => ['nullable', 'string'],
            'tokens' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'application_public_id' => ['nullable', 'string'],
        ]);

        $created = $this->developmentService->registerDefinition(
            app(TenantContext::class),
            ThemeDefinitionDto::fromArray($validated),
        );

        return (new ThemeDefinitionResource($created))->response()->setStatusCode(201);
    }

    public function showTheme(string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new ThemeDefinitionResource(
            $this->developmentService->findDefinitionByPublicId(app(TenantContext::class), $themePublicId),
        );
    }

    public function updateTheme(Request $request, string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('update', ThemeDefinition::class);
        $validated = $request->validate([
            'module_key' => ['sometimes', 'nullable', 'string', 'max:64'],
            'theme_key' => ['sometimes', 'string', 'max:128'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'scope' => ['sometimes', 'string'],
            'inheritance_mode' => ['sometimes', 'string'],
            'parent_theme_public_id' => ['sometimes', 'nullable', 'string'],
            'tokens' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'application_public_id' => ['sometimes', 'nullable', 'string'],
        ]);

        return new ThemeDefinitionResource(
            $this->developmentService->updateDefinitionByPublicId(
                app(TenantContext::class),
                $themePublicId,
                $validated,
            ),
        );
    }

    public function indexBrandProfiles(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return BrandProfileResource::collection(
            $this->developmentService->listBrandProfiles(app(TenantContext::class)),
        );
    }

    public function showBrandProfile(string $brandProfilePublicId): BrandProfileResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new BrandProfileResource(
            $this->developmentService->findBrandProfileByPublicId(app(TenantContext::class), $brandProfilePublicId),
        );
    }

    public function updateBrandProfile(Request $request, string $themePublicId): BrandProfileResource
    {
        $this->authorize('brand', ThemeDefinition::class);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:512'],
            'colors' => ['nullable', 'array'],
            'typography' => ['nullable', 'array'],
            'assets' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new BrandProfileResource(
            $this->developmentService->updateBrandProfile(
                app(TenantContext::class),
                $themePublicId,
                $validated,
            ),
        );
    }

    public function indexVersions(string $themePublicId): AnonymousResourceCollection
    {
        $this->authorize('view', ThemeDefinition::class);

        return ThemeVersionResource::collection(
            $this->developmentService->listVersionsForDefinition(app(TenantContext::class), $themePublicId),
        );
    }

    public function storeVersion(Request $request, string $themePublicId): JsonResponse
    {
        $this->authorize('create', ThemeDefinition::class);
        $validated = $request->validate([
            'snapshot' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string'],
        ]);

        $created = $this->developmentService->createThemeVersion(
            app(TenantContext::class),
            $themePublicId,
            $validated['snapshot'] ?? [],
            $validated['change_summary'] ?? null,
        );

        return (new ThemeVersionResource($created))->response()->setStatusCode(201);
    }

    public function publishTheme(Request $request, string $themePublicId): ThemeDefinitionResource
    {
        $this->authorize('publish', ThemeDefinition::class);
        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string'],
        ]);

        return new ThemeDefinitionResource(
            $this->developmentService->publishDefinition(
                app(TenantContext::class),
                $themePublicId,
                $validated['version_public_id'] ?? null,
            ),
        );
    }

    public function renderTheme(Request $request, string $themePublicId): ThemeRuntimeResource
    {
        $this->authorize('view', ThemeDefinition::class);

        return new ThemeRuntimeResource(
            $this->developmentService->renderDefinition(app(TenantContext::class), $themePublicId)->toArray(),
        );
    }

    public function runtime(Request $request): ThemeRuntimeResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);
        $validated = $request->validate([
            'theme_key' => ['nullable', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
        ]);

        return new ThemeRuntimeResource(
            $this->developmentService->composeRuntime(
                app(TenantContext::class),
                $validated['theme_key'] ?? 'default',
                $validated['module_key'] ?? null,
            ),
        );
    }

    public function statistics(): ThemeStatisticsResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return new ThemeStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }

    public function health(): ThemeHealthResource
    {
        $this->authorize('viewAny', ThemeDefinition::class);

        return new ThemeHealthResource($this->developmentService->health(app(TenantContext::class)));
    }
}

PHP);

echo "Generated M7 theme framework scaffold.\n";
