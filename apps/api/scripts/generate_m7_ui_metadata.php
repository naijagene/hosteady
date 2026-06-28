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

function exception(string $namespace, string $class, string $parent = 'UiException'): string
{
    return <<<PHP
<?php

namespace {$namespace};

class {$class} extends {$parent}
{
}

PHP;
}

$sdk = $base.'/app/Modules/Sdk/Ui';
$ns = 'App\\Modules\\Sdk\\Ui';

writeFile($sdk.'/Exceptions/UiException.php', exception("{$ns}\\Exceptions", 'UiException', '\\Exception'));
foreach (['UiPageNotFoundException', 'UiValidationException', 'UiRenderException', 'UiRegistryException'] as $ex) {
    writeFile($sdk.'/Exceptions/'.$ex.'.php', exception("{$ns}\\Exceptions", $ex));
}

$enums = [
    'UiPageStatus' => ['Draft', 'Published', 'Archived'],
    'UiPageType' => ['ModuleHome', 'EntityList', 'EntityDetail', 'EntityCreate', 'EntityEdit', 'Dashboard', 'Report', 'Workflow', 'Document', 'Settings', 'Custom'],
    'UiVisibility' => ['Public', 'Private', 'Organization', 'Workspace', 'Role'],
    'UiLayoutType' => ['SingleColumn', 'TwoColumn', 'ThreeColumn', 'Sidebar', 'HeaderContent', 'DashboardGrid', 'Tabbed', 'Wizard', 'SplitPane', 'Custom'],
    'UiRegionType' => ['Header', 'Sidebar', 'Content', 'Footer', 'Toolbar', 'Card', 'Tab', 'Modal', 'Drawer', 'WidgetArea', 'Custom'],
    'UiComponentType' => ['Form', 'Table', 'Dashboard', 'Report', 'Chart', 'Metric', 'DocumentList', 'NotificationList', 'WorkflowQueue', 'ApprovalQueue', 'ActivityFeed', 'NavigationMenu', 'Custom'],
    'UiBindingType' => ['Form', 'Table', 'Dashboard', 'Report', 'Entity', 'Workflow', 'Document', 'Notification', 'Static', 'Custom'],
    'UiActionType' => ['Navigate', 'SubmitForm', 'RefreshTable', 'OpenModal', 'DownloadReport', 'StartWorkflow', 'UploadDocument', 'SendNotification', 'Custom'],
    'UiBreakpointSize' => ['Xs', 'Sm', 'Md', 'Lg', 'Xl', 'Xxl'],
    'UiConditionOperator' => ['Equals', 'NotEquals', 'Contains', 'GreaterThan', 'LessThan', 'IsEmpty', 'IsNotEmpty', 'HasPermission', 'HasRole', 'FeatureEnabled'],
];
foreach ($enums as $class => $cases) {
    writeFile($sdk.'/Enums/'.$class.'.php', enum("{$ns}\\Enums", $class, $cases));
}

$dtos = [
    'UiPageDefinition' => ['publicId' => 'string', 'moduleKey' => '?string', 'pageKey' => 'string', 'name' => 'string', 'description' => '?string', 'pageType' => 'string', 'status' => 'string', 'visibility' => 'string', 'routePath' => '?string', 'icon' => '?string', 'layout' => 'array', 'regions' => 'array', 'components' => 'array', 'actions' => 'array', 'conditions' => 'array', 'breakpoints' => 'array', 'theme' => 'array', 'metadata' => 'array', 'applicationPublicId' => '?string'],
    'UiPageReference' => ['publicId' => 'string', 'moduleKey' => '?string', 'pageKey' => 'string', 'name' => 'string', 'pageType' => 'string', 'status' => 'string', 'routePath' => '?string'],
    'UiLayout' => ['publicId' => 'string', 'layoutKey' => 'string', 'name' => 'string', 'description' => '?string', 'layoutType' => 'string', 'status' => 'string', 'regions' => 'array', 'breakpoints' => 'array', 'metadata' => 'array', 'moduleKey' => '?string'],
    'UiLayoutRegion' => ['regionKey' => 'string', 'regionType' => 'string', 'label' => 'string', 'sortOrder' => 'int', 'components' => 'array', 'breakpoints' => 'array', 'metadata' => 'array'],
    'UiComponent' => ['publicId' => 'string', 'componentKey' => 'string', 'name' => 'string', 'description' => '?string', 'componentType' => 'string', 'status' => 'string', 'bindingType' => '?string', 'bindingConfig' => 'array', 'actions' => 'array', 'conditions' => 'array', 'metadata' => 'array', 'moduleKey' => '?string'],
    'UiComponentBinding' => ['bindingType' => 'string', 'publicId' => '?string', 'moduleKey' => '?string', 'resourceKey' => '?string', 'config' => 'array'],
    'UiComponentAction' => ['actionKey' => 'string', 'actionType' => 'string', 'label' => 'string', 'config' => 'array', 'conditions' => 'array'],
    'UiPageAction' => ['actionKey' => 'string', 'actionType' => 'string', 'label' => 'string', 'config' => 'array', 'conditions' => 'array'],
    'UiCondition' => ['field' => 'string', 'operator' => 'string', 'value' => '?string', 'metadata' => 'array'],
    'UiBreakpoint' => ['size' => 'string', 'minWidth' => 'int', 'metadata' => 'array'],
    'UiTheme' => ['themeKey' => 'string', 'name' => 'string', 'tokens' => 'array', 'metadata' => 'array'],
    'UiPersonalization' => ['publicId' => 'string', 'pagePublicId' => '?string', 'membershipPublicId' => '?string', 'personalization' => 'array', 'metadata' => 'array'],
    'UiRenderPayload' => ['page' => 'array', 'layout' => 'array', 'regions' => 'array', 'components' => 'array', 'actions' => 'array', 'conditions' => 'array', 'breakpoints' => 'array', 'theme' => 'array', 'personalization' => 'array', 'permissions' => 'array', 'runtimeContext' => 'array'],
    'UiRenderContext' => ['organizationPublicId' => 'string', 'workspacePublicId' => '?string', 'membershipPublicId' => '?string', 'moduleKey' => '?string', 'pageKey' => '?string', 'applicationPublicId' => '?string', 'capabilities' => 'array', 'metadata' => 'array'],
    'UiStatistics' => ['pages' => 'int', 'layouts' => 'int', 'components' => 'int', 'personalizations' => 'int', 'registeredModules' => 'int'],
    'UiHealthReport' => ['enabled' => 'bool', 'healthy' => 'bool', 'status' => 'string', 'pages' => 'int', 'layouts' => 'int', 'components' => 'int', 'personalizations' => 'int', 'warnings' => 'array', 'missingTables' => 'array', 'statistics' => 'array'],
];
foreach ($dtos as $class => $fields) {
    writeFile($sdk.'/Data/'.$class.'.php', dto("{$ns}\\Data", $class, $fields));
}

writeFile($sdk.'/Contracts/UiPageRegistry.php', contract("{$ns}\\Contracts", 'UiPageRegistry', <<<'M'
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiPageDefinition $definition): \App\Modules\Sdk\Ui\Data\UiPageDefinition;

    /** @return list<\App\Modules\Sdk\Ui\Data\UiPageDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $pageKey): \App\Modules\Sdk\Ui\Data\UiPageDefinition;

    public function findByRoutePath(string $organizationId, ?string $workspaceId, string $routePath): \App\Modules\Sdk\Ui\Data\UiPageDefinition;
M));
writeFile($sdk.'/Contracts/UiLayoutProvider.php', contract("{$ns}\\Contracts", 'UiLayoutProvider', <<<'M'
    /** @return list<\App\Modules\Sdk\Ui\Data\UiLayout> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiLayout $layout): \App\Modules\Sdk\Ui\Data\UiLayout;
M));
writeFile($sdk.'/Contracts/UiComponentProvider.php', contract("{$ns}\\Contracts", 'UiComponentProvider', <<<'M'
    /** @return list<\App\Modules\Sdk\Ui\Data\UiComponent> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiComponent $component): \App\Modules\Sdk\Ui\Data\UiComponent;
M));
writeFile($sdk.'/Contracts/UiRenderer.php', contract("{$ns}\\Contracts", 'UiRenderer', <<<'M'
    public function render(\App\Support\Tenant\TenantContext $context, string $moduleKey, string $pageKey): \App\Modules\Sdk\Ui\Data\UiRenderPayload;
M));
writeFile($sdk.'/Contracts/UiRuntimeComposer.php', contract("{$ns}\\Contracts", 'UiRuntimeComposer', <<<'M'
    public function compose(\App\Support\Tenant\TenantContext $context): \App\Modules\Sdk\Ui\Data\UiRenderPayload;

    /** @return array<string, mixed> */
    public function runtimeMetadata(\App\Support\Tenant\TenantContext $context): array;
M));
writeFile($sdk.'/Contracts/UiConditionEvaluator.php', contract("{$ns}\\Contracts", 'UiConditionEvaluator', <<<'M'
    public function evaluate(\App\Support\Tenant\TenantContext $context, array $conditions): bool;
M));
writeFile($sdk.'/Contracts/UiActionProvider.php', contract("{$ns}\\Contracts", 'UiActionProvider', <<<'M'
    /** @return list<array<string, mixed>> */
    public function pageActions(\App\Modules\Sdk\Ui\Data\UiPageDefinition $page): array;

    /** @return list<array<string, mixed>> */
    public function componentActions(\App\Modules\Sdk\Ui\Data\UiComponent $component): array;
M));
writeFile($sdk.'/Contracts/UiThemeProvider.php', contract("{$ns}\\Contracts", 'UiThemeProvider', <<<'M'
    public function themeForPage(\App\Modules\Sdk\Ui\Data\UiPageDefinition $page): \App\Modules\Sdk\Ui\Data\UiTheme;
M));
writeFile($sdk.'/Contracts/UiPersonalizationProvider.php', contract("{$ns}\\Contracts", 'UiPersonalizationProvider', <<<'M'
    public function get(\App\Support\Tenant\TenantContext $context, string $pagePublicId): \App\Modules\Sdk\Ui\Data\UiPersonalization;

    public function update(\App\Support\Tenant\TenantContext $context, string $pagePublicId, array $personalization): \App\Modules\Sdk\Ui\Data\UiPersonalization;
M));

echo "M7-002 SDK generated.\n";
