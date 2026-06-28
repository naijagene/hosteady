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
    echo "Wrote: {$path}\n";
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

function exception(string $namespace, string $class, string $parent = 'ApplicationException'): string
{
    return <<<PHP
<?php

namespace {$namespace};

class {$class} extends {$parent}
{
}

PHP;
}

$sdk = $base.'/app/Modules/Sdk/Application';
$ns = 'App\\Modules\\Sdk\\Application';

writeFile($sdk.'/Exceptions/ApplicationException.php', exception("{$ns}\\Exceptions", 'ApplicationException', '\\Exception'));
writeFile($sdk.'/Exceptions/ApplicationRegistrationException.php', exception("{$ns}\\Exceptions", 'ApplicationRegistrationException'));
writeFile($sdk.'/Exceptions/ApplicationRuntimeException.php', exception("{$ns}\\Exceptions", 'ApplicationRuntimeException'));
writeFile($sdk.'/Exceptions/NavigationException.php', exception("{$ns}\\Exceptions", 'NavigationException'));

writeFile($sdk.'/Enums/ApplicationStatus.php', enum("{$ns}\\Enums", 'ApplicationStatus', ['Registered', 'Enabled', 'Disabled', 'Archived']));
writeFile($sdk.'/Enums/NavigationItemType.php', enum("{$ns}\\Enums", 'NavigationItemType', ['Group', 'Item', 'Divider', 'Link', 'Module']));
writeFile($sdk.'/Enums/WorkspaceStatus.php', enum("{$ns}\\Enums", 'WorkspaceStatus', ['Active', 'Inactive', 'Archived']));
writeFile($sdk.'/Enums/ApplicationVisibility.php', enum("{$ns}\\Enums", 'ApplicationVisibility', ['Public', 'Private', 'Organization', 'Workspace']));
writeFile($sdk.'/Enums/ApplicationType.php', enum("{$ns}\\Enums", 'ApplicationType', ['Core', 'Business', 'Custom', 'Module']));

$dtos = [
    'ApplicationDefinition' => ['publicId' => 'string', 'applicationKey' => 'string', 'name' => 'string', 'description' => '?string', 'applicationType' => 'string', 'status' => 'string', 'visibility' => 'string', 'moduleKey' => '?string', 'catalogApplicationPublicId' => '?string', 'manifest' => 'array', 'metadata' => 'array'],
    'ApplicationManifest' => ['applicationKey' => 'string', 'name' => 'string', 'version' => 'string', 'type' => 'string', 'capabilities' => 'array', 'dependencies' => 'array', 'navigation' => 'array', 'metadata' => 'array'],
    'ApplicationReference' => ['publicId' => 'string', 'applicationKey' => 'string', 'name' => 'string', 'status' => 'string'],
    'ApplicationRuntimeMetadata' => ['applicationKey' => 'string', 'enabled' => 'bool', 'capabilities' => 'array', 'navigation' => 'array', 'menus' => 'array', 'workspace' => 'array', 'metadata' => 'array'],
    'ApplicationWorkspace' => ['publicId' => 'string', 'workspaceKey' => 'string', 'name' => 'string', 'status' => 'string', 'applicationPublicId' => 'string', 'metadata' => 'array'],
    'NavigationMenu' => ['menuKey' => 'string', 'label' => 'string', 'groups' => 'array', 'metadata' => 'array'],
    'NavigationGroup' => ['groupKey' => 'string', 'label' => 'string', 'sortOrder' => 'int', 'items' => 'array', 'metadata' => 'array'],
    'NavigationItem' => ['itemKey' => 'string', 'label' => 'string', 'itemType' => 'string', 'route' => 'array', 'badge' => 'array', 'sortOrder' => 'int', 'requiredPermission' => '?string', 'metadata' => 'array'],
    'NavigationBadge' => ['label' => 'string', 'variant' => 'string', 'count' => 'int'],
    'NavigationRoute' => ['name' => 'string', 'path' => 'string', 'moduleKey' => '?string', 'parameters' => 'array'],
    'ApplicationStatistics' => ['registeredApps' => 'int', 'enabledApps' => 'int', 'navigationNodes' => 'int', 'workspaceCount' => 'int'],
    'ApplicationHealthReport' => ['enabled' => 'bool', 'healthy' => 'bool', 'status' => 'string', 'registeredApps' => 'int', 'enabledApps' => 'int', 'warnings' => 'array', 'missingTables' => 'array', 'statistics' => 'array'],
];

foreach ($dtos as $class => $fields) {
    writeFile($sdk.'/Data/'.$class.'.php', dto("{$ns}\\Data", $class, $fields));
}

writeFile($sdk.'/Contracts/ApplicationRuntime.php', contract("{$ns}\\Contracts", 'ApplicationRuntime', <<<'M'
    public function load(\App\Support\Tenant\TenantContext $context): \App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata;

    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationDefinition> */
    public function listApplications(\App\Support\Tenant\TenantContext $context): array;
M));
writeFile($sdk.'/Contracts/ApplicationRegistry.php', contract("{$ns}\\Contracts", 'ApplicationRegistry', <<<'M'
    public function register(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Application\Data\ApplicationDefinition $definition): \App\Modules\Sdk\Application\Data\ApplicationDefinition;

    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByPublicId(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
M));
writeFile($sdk.'/Contracts/NavigationProvider.php', contract("{$ns}\\Contracts", 'NavigationProvider', <<<'M'
    /** @return list<\App\Modules\Sdk\Application\Data\NavigationMenu> */
    public function navigation(\App\Support\Tenant\TenantContext $context): array;
M));
writeFile($sdk.'/Contracts/MenuProvider.php', contract("{$ns}\\Contracts", 'MenuProvider', <<<'M'
    /** @return list<\App\Modules\Sdk\Application\Data\NavigationMenu> */
    public function menus(\App\Support\Tenant\TenantContext $context): array;
M));
writeFile($sdk.'/Contracts/WorkspaceProvider.php', contract("{$ns}\\Contracts", 'WorkspaceProvider', <<<'M'
    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationWorkspace> */
    public function workspaces(\App\Support\Tenant\TenantContext $context): array;
M));
writeFile($sdk.'/Contracts/ApplicationProvider.php', contract("{$ns}\\Contracts", 'ApplicationProvider', <<<'M'
    public function application(\App\Support\Tenant\TenantContext $context, string $applicationKey): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
M));
writeFile($sdk.'/Contracts/ApplicationManifestProvider.php', contract("{$ns}\\Contracts", 'ApplicationManifestProvider', <<<'M'
    public function manifest(string $applicationKey): \App\Modules\Sdk\Application\Data\ApplicationManifest;
M));
writeFile($sdk.'/Contracts/ApplicationLifecycle.php', contract("{$ns}\\Contracts", 'ApplicationLifecycle', <<<'M'
    public function enable(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;

    public function disable(string $organizationId, ?string $workspaceId, string $publicId): \App\Modules\Sdk\Application\Data\ApplicationDefinition;
M));

echo "M7 SDK generated.\n";
