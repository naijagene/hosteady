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

function enum(string $namespace, string $class, array $cases): string
{
    $body = implode("\n", array_map(fn ($c) => "    case {$c} = '".strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $c))."';", $cases));

    return <<<PHP
<?php

namespace {$namespace};

enum {$class}: string
{
{$body}
}

PHP;
}

function contract(string $namespace, string $class, string $methods): string
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

function exception(string $namespace, string $class, string $parent = 'NavigationException'): string
{
    return <<<PHP
<?php

namespace {$namespace};

class {$class} extends {$parent}
{
}

PHP;
}

$sdk = $base.'/app/Modules/Sdk/Navigation';
$ns = 'App\\Modules\\Sdk\\Navigation';

writeFile($sdk.'/Exceptions/NavigationException.php', exception("{$ns}\\Exceptions", 'NavigationException', '\\Exception'));
foreach (['NavigationNotFoundException', 'NavigationValidationException', 'NavigationRenderException', 'NavigationRegistryException', 'NavigationPublishException'] as $ex) {
    writeFile($sdk.'/Exceptions/'.$ex.'.php', exception("{$ns}\\Exceptions", $ex));
}

$enums = [
    'NavigationDefinitionStatus' => ['Draft', 'Published', 'Archived'],
    'NavigationVersionStatus' => ['Draft', 'Published', 'Archived'],
    'NavigationItemType' => ['Link', 'Group', 'Divider', 'External', 'Action', 'Custom'],
    'NavigationType' => ['Primary', 'Secondary', 'Sidebar', 'Footer', 'Custom'],
    'NavigationVisibility' => ['Public', 'Private', 'Authenticated', 'Organization', 'Workspace', 'Role'],
    'NavigationScope' => ['Organization', 'Workspace', 'Application'],
    'NavigationConditionOperator' => ['Equals', 'NotEquals', 'Contains', 'GreaterThan', 'LessThan', 'IsEmpty', 'IsNotEmpty', 'HasPermission', 'HasRole', 'FeatureEnabled'],
];
foreach ($enums as $class => $cases) {
    writeFile($sdk.'/Enums/'.$class.'.php', enum("{$ns}\\Enums", $class, $cases));
}

$dtos = [
    'NavigationDefinition' => ['publicId' => 'string', 'moduleKey' => '?string', 'navigationKey' => 'string', 'name' => 'string', 'description' => '?string', 'type' => 'string', 'status' => 'string', 'visibility' => 'string', 'scope' => 'string', 'structure' => 'array', 'conditions' => 'array', 'metadata' => 'array', 'applicationPublicId' => '?string', 'currentVersionPublicId' => '?string'],
    'NavigationVersion' => ['publicId' => 'string', 'navigationDefinitionPublicId' => 'string', 'versionNumber' => 'int', 'status' => 'string', 'structure' => 'array', 'conditions' => 'array', 'changeSummary' => '?string', 'metadata' => 'array', 'publishedAt' => '?string'],
    'NavigationItem' => ['publicId' => 'string', 'navigationDefinitionPublicId' => '?string', 'parentItemPublicId' => '?string', 'moduleKey' => '?string', 'itemKey' => 'string', 'label' => 'string', 'itemType' => 'string', 'route' => '?string', 'icon' => '?string', 'badge' => 'array', 'visibility' => 'string', 'conditions' => 'array', 'permissions' => 'array', 'roles' => 'array', 'sortOrder' => 'int', 'metadata' => 'array', 'applicationPublicId' => '?string'],
    'NavigationPersonalization' => ['publicId' => 'string', 'navigationDefinitionPublicId' => '?string', 'membershipPublicId' => '?string', 'personalization' => 'array', 'metadata' => 'array'],
    'NavigationCondition' => ['field' => 'string', 'operator' => 'string', 'value' => '?string', 'metadata' => 'array'],
    'NavigationTreeNode' => ['item' => 'array', 'children' => 'array', 'depth' => 'int'],
    'NavigationTree' => ['nodes' => 'array', 'warnings' => 'array'],
    'NavigationRenderPayload' => ['definition' => 'array', 'version' => 'array', 'tree' => 'array', 'items' => 'array', 'permissions' => 'array', 'personalization' => 'array', 'runtimeContext' => 'array', 'warnings' => 'array'],
    'NavigationStatistics' => ['definitions' => 'int', 'versions' => 'int', 'items' => 'int', 'personalizations' => 'int', 'registeredModules' => 'int'],
    'NavigationHealthReport' => ['enabled' => 'bool', 'healthy' => 'bool', 'status' => 'string', 'definitions' => 'int', 'versions' => 'int', 'items' => 'int', 'personalizations' => 'int', 'warnings' => 'array', 'missingTables' => 'array', 'statistics' => 'array'],
];
foreach ($dtos as $class => $fields) {
    writeFile($sdk.'/Data/'.$class.'.php', dto("{$ns}\\Data", $class, $fields));
}

writeFile($sdk.'/Contracts/NavigationRegistry.php', contract("{$ns}\\Contracts", 'NavigationRegistry', <<<'M'
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Navigation\Data\NavigationDefinition $definition): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;

    /** @return list<\App\Modules\Sdk\Navigation\Data\NavigationDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $navigationKey): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;
M));
writeFile($sdk.'/Contracts/NavigationTreeBuilder.php', contract("{$ns}\\Contracts", 'NavigationTreeBuilder', <<<'M'
    /** @param  list<\App\Modules\Sdk\Navigation\Data\NavigationItem>  $items */
    public function build(array $items): \App\Modules\Sdk\Navigation\Data\NavigationTree;
M));
writeFile($sdk.'/Contracts/NavigationVisibilityResolver.php', contract("{$ns}\\Contracts", 'NavigationVisibilityResolver', <<<'M'
    public function isVisible(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Navigation\Data\NavigationItem $item): bool;

    public function evaluate(\App\Support\Tenant\TenantContext $context, array $conditions, array $values = []): bool;
M));
writeFile($sdk.'/Contracts/NavigationDraftManager.php', contract("{$ns}\\Contracts", 'NavigationDraftManager', <<<'M'
    public function saveDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, array $structure, ?string $moduleKey = null): \App\Modules\Sdk\Navigation\Data\NavigationVersion;

    public function loadDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): ?\App\Modules\Sdk\Navigation\Data\NavigationVersion;

    public function discardDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): void;
M));
writeFile($sdk.'/Contracts/NavigationVersionManager.php', contract("{$ns}\\Contracts", 'NavigationVersionManager', <<<'M'
    /** @return list<\App\Modules\Sdk\Navigation\Data\NavigationVersion> */
    public function listVersions(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): array;

    public function findVersion(\App\Support\Tenant\TenantContext $context, string $versionPublicId): \App\Modules\Sdk\Navigation\Data\NavigationVersion;
M));
writeFile($sdk.'/Contracts/NavigationPublisher.php', contract("{$ns}\\Contracts", 'NavigationPublisher', <<<'M'
    public function publish(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $versionPublicId = null, ?string $moduleKey = null): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;
M));
writeFile($sdk.'/Contracts/NavigationRenderer.php', contract("{$ns}\\Contracts", 'NavigationRenderer', <<<'M'
    public function render(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null, bool $previewDraft = false): \App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
M));
writeFile($sdk.'/Contracts/NavigationPersonalizationProvider.php', contract("{$ns}\\Contracts", 'NavigationPersonalizationProvider', <<<'M'
    public function get(\App\Support\Tenant\TenantContext $context, string $navigationDefinitionPublicId): \App\Modules\Sdk\Navigation\Data\NavigationPersonalization;

    public function update(\App\Support\Tenant\TenantContext $context, string $navigationDefinitionPublicId, array $personalization): \App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
M));

$models = [
    'NavigationDefinition' => [
        'table' => 'navigation_definitions',
        'fillable' => ['public_id', 'organization_id', 'workspace_id', 'application_id', 'module_key', 'navigation_key', 'name', 'description', 'type', 'status', 'visibility', 'scope', 'current_version_id', 'structure_json', 'conditions_json', 'metadata', 'created_by_user_id', 'created_membership_id'],
        'casts' => ['structure_json' => 'array', 'conditions_json' => 'array', 'metadata' => 'array'],
        'softDeletes' => true,
        'relations' => [
            "public function versions(): HasMany\n    {\n        return \$this->hasMany(NavigationVersion::class, 'navigation_definition_id');\n    }",
            "public function items(): HasMany\n    {\n        return \$this->hasMany(NavigationItem::class, 'navigation_definition_id');\n    }",
            "public function currentVersion(): BelongsTo\n    {\n        return \$this->belongsTo(NavigationVersion::class, 'current_version_id');\n    }",
        ],
    ],
    'NavigationVersion' => [
        'table' => 'navigation_versions',
        'fillable' => ['public_id', 'organization_id', 'workspace_id', 'navigation_definition_id', 'version_number', 'status', 'structure_json', 'conditions_json', 'change_summary', 'metadata', 'published_at', 'published_by_user_id', 'published_by_membership_id'],
        'casts' => ['structure_json' => 'array', 'conditions_json' => 'array', 'metadata' => 'array', 'published_at' => 'datetime'],
        'softDeletes' => true,
        'relations' => [
            "public function definition(): BelongsTo\n    {\n        return \$this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');\n    }",
        ],
    ],
    'NavigationItem' => [
        'table' => 'navigation_items',
        'fillable' => ['public_id', 'organization_id', 'workspace_id', 'navigation_definition_id', 'parent_item_id', 'application_id', 'module_key', 'item_key', 'label', 'item_type', 'route', 'icon', 'badge_json', 'visibility', 'conditions_json', 'permissions_json', 'roles_json', 'sort_order', 'metadata'],
        'casts' => ['badge_json' => 'array', 'conditions_json' => 'array', 'permissions_json' => 'array', 'roles_json' => 'array', 'metadata' => 'array'],
        'softDeletes' => true,
        'relations' => [
            "public function definition(): BelongsTo\n    {\n        return \$this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');\n    }",
            "public function parent(): BelongsTo\n    {\n        return \$this->belongsTo(NavigationItem::class, 'parent_item_id');\n    }",
            "public function children(): HasMany\n    {\n        return \$this->hasMany(NavigationItem::class, 'parent_item_id')->orderBy('sort_order');\n    }",
        ],
    ],
    'NavigationPersonalization' => [
        'table' => 'navigation_personalizations',
        'fillable' => ['public_id', 'organization_id', 'workspace_id', 'membership_id', 'navigation_definition_id', 'personalization_json', 'metadata'],
        'casts' => ['personalization_json' => 'array', 'metadata' => 'array'],
        'softDeletes' => true,
        'relations' => [],
    ],
    'NavigationActivityLog' => [
        'table' => 'navigation_activity_logs',
        'fillable' => ['public_id', 'organization_id', 'workspace_id', 'navigation_definition_id', 'navigation_item_id', 'action', 'before_state', 'after_state', 'actor_user_id', 'actor_membership_id', 'metadata', 'created_at'],
        'casts' => ['before_state' => 'array', 'after_state' => 'array', 'metadata' => 'array', 'created_at' => 'datetime'],
        'softDeletes' => false,
        'timestamps' => false,
        'relations' => [
            "public function definition(): BelongsTo\n    {\n        return \$this->belongsTo(NavigationDefinition::class, 'navigation_definition_id');\n    }",
        ],
    ],
];

foreach ($models as $class => $config) {
    $uses = ["use App\\Models\\Concerns\\HasHeosPublicId;", "use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;", "use Illuminate\\Database\\Eloquent\\Model;"];
    if ($config['softDeletes']) {
        $uses[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
    }
    if ($config['relations'] !== []) {
        $uses[] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
        $uses[] = 'use Illuminate\Database\Eloquent\Relations\HasMany;';
    }
    $traits = ['HasHeosPublicId', 'HasUuids'];
    if ($config['softDeletes']) {
        $traits[] = 'SoftDeletes';
    }
    $fillable = var_export($config['fillable'], true);
    $castsBody = implode(",\n            ", array_map(fn ($k, $v) => "'{$k}' => '{$v}'", array_keys($config['casts']), $config['casts']));
    $relationsBody = implode("\n\n    ", $config['relations']);
    $timestamps = ($config['timestamps'] ?? true) ? '' : "\n    public \$timestamps = false;";
    $relationsSection = $relationsBody !== '' ? "\n\n    {$relationsBody}" : '';
    $useStatements = implode("\n", $uses);
    $traitList = implode(', ', $traits);

    writeFile($base.'/app/Models/'.$class.'.php', <<<PHP
<?php

namespace App\Models;

{$useStatements}

class {$class} extends Model
{
    use {$traitList};

    protected \$table = '{$config['table']}';

    public \$incrementing = false;

    protected \$keyType = 'string';{$timestamps}

    /** @var list<string> */
    protected \$fillable = {$fillable};

    protected function casts(): array
    {
        return [
            {$castsBody},
        ];
    }{$relationsSection}
}

PHP);
}

echo "Generated M7 navigation designer SDK and models.\n";
