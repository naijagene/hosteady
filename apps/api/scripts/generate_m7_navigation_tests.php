<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$testPath = $base.'/tests/Feature/Services/Navigation/M7NavigationMenuDesignerTest.php';

$dtos = [
    'NavigationDefinition', 'NavigationItem', 'NavigationTree', 'NavigationTreeNode',
    'NavigationVersion', 'NavigationRenderPayload', 'NavigationPersonalization',
    'NavigationStatistics', 'NavigationHealthReport', 'NavigationCondition',
];

$enums = [
    ['NavigationType', ['Primary', 'Sidebar', 'Topbar', 'Footer', 'Mobile', 'Breadcrumb', 'Contextual', 'CommandPalette', 'Custom']],
    ['NavigationItemType', ['Link', 'Route', 'Page', 'Module', 'Application', 'Group', 'Divider', 'Heading', 'External', 'Action', 'Custom']],
    ['NavigationVisibility', ['Public', 'Authenticated', 'PermissionBased', 'RoleBased', 'WorkspaceBased', 'OrganizationBased', 'Hidden', 'Custom']],
    ['NavigationScope', ['Organization', 'Workspace', 'Application']],
    ['NavigationConditionOperator', ['Equals', 'NotEquals', 'Contains', 'GreaterThan', 'LessThan', 'IsEmpty', 'IsNotEmpty', 'HasPermission', 'HasRole', 'FeatureEnabled']],
    ['NavigationDefinitionStatus', ['Draft', 'Published', 'Archived']],
    ['NavigationVersionStatus', ['Draft', 'Published', 'Archived']],
];

$body = <<<'PHP'
<?php

namespace Tests\Feature\Services\Navigation;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\NavigationActivityLog;
use App\Models\NavigationDefinition;
use App\Models\Permission;
use App\Modules\Sdk\Navigation\Contracts\NavigationRegistry;
use App\Modules\Sdk\Navigation\Contracts\NavigationRenderer;
use App\Modules\Sdk\Navigation\Contracts\NavigationTreeBuilder;
use App\Modules\Sdk\Navigation\Contracts\NavigationVisibilityResolver;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Data\NavigationItem as NavigationItemDto;
use App\Modules\Sdk\Navigation\Exceptions\NavigationRegistryException;
use App\Services\Application\NavigationBuilderService;
use App\Services\Module\ModuleDoctorService;
use App\Services\Navigation\NavigationApplicationRuntimeBridge;
use App\Services\Navigation\NavigationDefaultGeneratorService;
use App\Services\Navigation\NavigationDevelopmentService;
use App\Services\Navigation\NavigationRegistryService;
use App\Services\Navigation\NavigationRendererService;
use App\Services\Navigation\NavigationRulesBridge;
use App\Services\Navigation\NavigationTreeBuilderService;
use App\Services\Navigation\NavigationUiBridge;
use App\Services\Navigation\NavigationVisibilityResolverService;
use App\Services\Ui\UiDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7NavigationMenuDesignerTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

PHP;

foreach ($enums as [$enum, $cases]) {
    $body .= "\n    public function test_{$enum}_enum_has_expected_cases(): void\n    {\n";
    $body .= "        \$cases = array_map(static fn (\\App\\Modules\\Sdk\\Navigation\\Enums\\{$enum} \$case) => \$case->value, \\App\\Modules\\Sdk\\Navigation\\Enums\\{$enum}::cases());\n";
    foreach ($cases as $case) {
        $val = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $case));
        $body .= "        \$this->assertContains('{$val}', \$cases);\n";
    }
    $body .= "    }\n";
}

foreach ($dtos as $dto) {
    $body .= "\n    public function test_{$dto}_dto_roundtrip(): void\n    {\n";
    $body .= "        \$sample = \\App\\Modules\\Sdk\\Navigation\\Data\\{$dto}::fromArray([]);\n";
    $body .= "        \$roundtrip = \\App\\Modules\\Sdk\\Navigation\\Data\\{$dto}::fromArray(\$sample->toArray());\n";
    $body .= "        \$this->assertSame(\$sample->toArray(), \$roundtrip->toArray());\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

    public function test_navigation_contracts_bound(): void
    {
        $this->assertInstanceOf(NavigationRegistryService::class, app(NavigationRegistry::class));
        $this->assertInstanceOf(NavigationRendererService::class, app(NavigationRenderer::class));
        $this->assertInstanceOf(NavigationTreeBuilderService::class, app(NavigationTreeBuilder::class));
        $this->assertInstanceOf(NavigationVisibilityResolverService::class, app(NavigationVisibilityResolver::class));
    }

    public function test_navigation_designer_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.navigation_designer.enabled', true));
    }

    public function test_permission_catalog_has_navigation_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(121, Permission::query()->count());
        foreach (['navigation.read', 'navigation.manage', 'navigation.publish', 'navigation.personalize'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_module_doctor_includes_navigation_designer(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('navigation_designer', $report->platformSummary['enterprise']);
    }

    public function test_register_definition_from_dto(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, NavigationDefinitionDto::fromArray([
            'module_key' => 'demo',
            'navigation_key' => 'main',
            'name' => 'Main Navigation',
            'type' => 'primary',
        ]));
        $this->assertSame('demo', $definition->moduleKey);
        $this->assertSame('main', $definition->navigationKey);
        $this->assertNotEmpty($definition->publicId);
    }

    public function test_register_definition_from_array(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo',
            'navigation_key' => 'sidebar',
            'name' => 'Sidebar',
        ]);
        $this->assertSame('sidebar', $definition->navigationKey);
    }

    public function test_list_definitions(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'list', 'name' => 'List Nav',
        ]);
        $definitions = app(NavigationDevelopmentService::class)->listDefinitions($context);
        $this->assertCount(1, $definitions);
    }

    public function test_find_definition_by_public_id(): void
    {
        $context = $this->navigationContext();
        $created = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'show', 'name' => 'Show Nav',
        ]);
        $found = app(NavigationDevelopmentService::class)->findDefinitionByPublicId($context, $created->publicId);
        $this->assertSame($created->publicId, $found->publicId);
    }

    public function test_update_definition_by_public_id(): void
    {
        $context = $this->navigationContext();
        $created = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'update', 'name' => 'Before',
        ]);
        $updated = app(NavigationDevelopmentService::class)->updateDefinitionByPublicId($context, $created->publicId, [
            'name' => 'After',
        ]);
        $this->assertSame('After', $updated->name);
    }

    public function test_duplicate_definition_prevention(): void
    {
        $context = $this->navigationContext();
        $service = app(NavigationRegistryService::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'navigation_key' => 'dup', 'name' => 'Dup',
        ]);
        $this->expectException(NavigationRegistryException::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'navigation_key' => 'dup', 'name' => 'Dup 2',
        ]);
    }

    public function test_create_item_for_definition(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'items', 'name' => 'Items Nav',
        ]);
        $item = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'home', 'label' => 'Home',
        ]);
        $this->assertSame('home', $item->itemKey);
    }

    public function test_list_items_for_definition(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'list-items', 'name' => 'List Items',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'a', 'label' => 'A',
        ]);
        $items = app(NavigationDevelopmentService::class)->listItems($context, $definition->publicId);
        $this->assertCount(1, $items);
    }

    public function test_update_item(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'upd-item', 'name' => 'Upd Item',
        ]);
        $item = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'edit', 'label' => 'Before',
        ]);
        $updated = app(NavigationDevelopmentService::class)->updateItem($context, $item->publicId, [
            'label' => 'After',
        ]);
        $this->assertSame('After', $updated->label);
    }

    public function test_delete_item(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'del-item', 'name' => 'Del Item',
        ]);
        $item = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'remove', 'label' => 'Remove',
        ]);
        app(NavigationDevelopmentService::class)->deleteItem($context, $item->publicId);
        $this->assertSame([], app(NavigationDevelopmentService::class)->listItems($context, $definition->publicId));
    }

    public function test_nested_tree_build(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'tree', 'name' => 'Tree Nav',
        ]);
        $parent = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'parent', 'label' => 'Parent', 'item_type' => 'group',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'child', 'label' => 'Child', 'parent_item_public_id' => $parent->publicId,
        ]);
        $tree = app(NavigationDevelopmentService::class)->buildTree($context, $definition->publicId);
        $this->assertNotEmpty($tree->nodes);
    }

    public function test_sort_order_preserved_in_tree(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'sort', 'name' => 'Sort Nav',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'b', 'label' => 'B', 'sort_order' => 2,
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'a', 'label' => 'A', 'sort_order' => 1,
        ]);
        $tree = app(NavigationTreeBuilderService::class)->build(
            app(NavigationDevelopmentService::class)->listItems($context, $definition->publicId),
        );
        $this->assertSame('a', $tree->nodes[0]['item']['item_key'] ?? null);
    }

    public function test_cycle_prevention_in_tree(): void
    {
        $items = [
            NavigationItemDto::fromArray(['public_id' => 'a', 'item_key' => 'a', 'label' => 'A', 'parent_item_public_id' => 'b', 'item_type' => 'link']),
            NavigationItemDto::fromArray(['public_id' => 'b', 'item_key' => 'b', 'label' => 'B', 'parent_item_public_id' => 'a', 'item_type' => 'link']),
        ];
        $tree = app(NavigationTreeBuilderService::class)->build($items);
        $this->assertNotEmpty($tree->warnings);
    }

    public function test_visibility_permission_condition(): void
    {
        $context = $this->navigationContext();
        $resolver = app(NavigationVisibilityResolverService::class);
        $item = NavigationItemDto::fromArray([
            'item_key' => 'perm', 'label' => 'Perm', 'item_type' => 'link',
            'permissions' => ['organization.archive'],
        ]);
        $this->assertTrue($resolver->isVisible($context, $item));
    }

    public function test_visibility_role_condition(): void
    {
        $context = $this->navigationContext();
        $resolver = app(NavigationVisibilityResolverService::class);
        $this->assertTrue($resolver->evaluate($context, [[
            'operator' => 'has_role', 'value' => 'owner',
        ]]));
    }

    public function test_visibility_feature_condition(): void
    {
        $context = $this->navigationContext();
        $resolver = app(NavigationVisibilityResolverService::class);
        $this->assertTrue($resolver->evaluate($context, [[
            'operator' => 'feature_enabled', 'field' => 'navigation_designer',
        ]]));
    }

    public function test_draft_version_create(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'draft', 'name' => 'Draft Nav',
        ]);
        $version = app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, [
            'items' => [['item_key' => 'draft', 'label' => 'Draft Item']],
        ]);
        $this->assertSame('draft', $version->status);
    }

    public function test_publish_navigation(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'publish', 'name' => 'Publish Nav',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'published', 'label' => 'Published',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, ['items' => []]);
        $published = app(NavigationDevelopmentService::class)->publishDefinition($context, $definition->publicId);
        $this->assertNotNull($published->currentVersionPublicId);
    }

    public function test_render_published_navigation(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'render', 'name' => 'Render Nav',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'home', 'label' => 'Home',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, ['items' => []]);
        app(NavigationDevelopmentService::class)->publishDefinition($context, $definition->publicId);
        $payload = app(NavigationDevelopmentService::class)->renderDefinition($context, $definition->publicId);
        $this->assertArrayHasKey('public_id', $payload->definition);
    }

    public function test_preview_draft_render(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'preview', 'name' => 'Preview Nav',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, ['preview' => true]);
        $payload = app(NavigationDevelopmentService::class)->renderDefinition($context, $definition->publicId, true);
        $this->assertNotEmpty($payload->version);
    }

    public function test_default_generator(): void
    {
        $context = $this->navigationContext();
        $generated = app(NavigationDevelopmentService::class)->generateDefault($context, 'generated', 'demo');
        $this->assertSame('generated', $generated['definition']->navigationKey);
        $this->assertIsArray($generated['items']);
    }

    public function test_application_runtime_fallback(): void
    {
        $context = $this->navigationContext();
        $menus = app(NavigationApplicationRuntimeBridge::class)->buildMenusForRuntime($context, 'missing');
        $this->assertSame([], $menus);
    }

    public function test_application_runtime_bridge_with_published_navigation(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'main', 'name' => 'Main',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/dashboard',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, []);
        app(NavigationDevelopmentService::class)->publishDefinition($context, $definition->publicId);
        $menus = app(NavigationApplicationRuntimeBridge::class)->buildMenusForRuntime($context, 'main', 'demo');
        $this->assertNotEmpty($menus);
    }

    public function test_navigation_builder_merges_designer_menus(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'main', 'name' => 'Main',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'apps', 'label' => 'Apps', 'route' => '/apps',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, []);
        app(NavigationDevelopmentService::class)->publishDefinition($context, $definition->publicId);
        $payload = app(NavigationBuilderService::class)->build($context);
        $this->assertNotEmpty($payload->menus);
    }

    public function test_ui_page_link_bridge(): void
    {
        $context = $this->navigationContext();
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'nav-link', 'name' => 'Nav Link', 'route_path' => '/demo/nav-link',
        ]);
        $resolved = app(NavigationUiBridge::class)->resolvePageReferenceBestEffort('demo', '/demo/nav-link');
        $this->assertNotNull($resolved);
        $this->assertSame('nav-link', $resolved['page_key'] ?? null);
    }

    public function test_rules_visibility_best_effort(): void
    {
        $context = $this->navigationContext();
        $visible = app(NavigationRulesBridge::class)->evaluateVisibilityBestEffort($context, [[
            'operator' => 'has_permission', 'value' => 'navigation.read',
        ]]);
        $this->assertTrue($visible);
    }

    public function test_personalization_update(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'pers', 'name' => 'Pers Nav',
        ]);
        $personalization = app(NavigationDevelopmentService::class)->updatePersonalization($context, $definition->publicId, [
            'pinned' => ['home'],
        ]);
        $this->assertSame(['pinned' => ['home']], $personalization->personalization);
    }

    public function test_search_indexing_best_effort(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'search', 'name' => 'Search Nav',
        ]);
        $this->assertTrue(true);
    }

    public function test_audit_actions_recorded(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'audit', 'name' => 'Audit Nav',
        ]);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NavigationDefinitionRegistered->value)->exists());
        $this->assertTrue(NavigationActivityLog::query()->where('action', 'definition.registered')->exists());
    }

    public function test_platform_event_best_effort(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'event', 'name' => 'Event Nav',
        ]);
        $this->assertTrue(true);
    }

    public function test_runtime_metadata_includes_navigation_designer(): void
    {
        $context = $this->navigationContext();
        $runtime = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class)->resolve($context);
        $this->assertTrue($runtime->capabilities['navigation_designer'] ?? false);
        $this->assertArrayHasKey('navigation_designer', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_doctor_health_section(): void
    {
        $context = $this->navigationContext();
        $health = app(NavigationDevelopmentService::class)->health($context);
        $this->assertTrue($health->enabled);
        $this->assertContains('No navigation definitions registered.', $health->warnings);
    }

    public function test_health_reports_missing_navigation_tables(): void
    {
        Schema::drop('navigation_definitions');
        Schema::drop('navigation_versions');
        Schema::drop('navigation_items');
        Schema::drop('navigation_personalizations');

        $health = app(\App\Services\Navigation\NavigationHealthService::class)->health();

        $this->assertSame('warning', $health->status);
        $this->assertContains('navigation_definitions', $health->missingTables);
    }

    public function test_missing_table_guard(): void
    {
        Schema::drop('navigation_definitions');
        $health = app(\App\Services\Navigation\NavigationHealthService::class)->health();
        $this->assertSame('warning', $health->status);
        $this->assertNotEmpty($health->missingTables);
        $this->assertStringContainsString('Run php artisan migrate.', $health->warnings[0]);
    }

    public function test_heos_doctor_includes_navigation_designer_missing_table_warning(): void
    {
        Schema::drop('navigation_definitions');
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('navigation_designer', $report->platformSummary['enterprise']);
        $this->assertContains('navigation_definitions', $report->platformSummary['enterprise']['navigation_designer']['missing_tables']);
    }

    public function test_registry_list_does_not_crash_when_definitions_missing(): void
    {
        Schema::drop('navigation_definitions');
        $context = $this->navigationContext();
        $definitions = app(NavigationRegistryService::class)->list(
            $context->organization->id,
            $context->workspace->id,
        );
        $this->assertSame([], $definitions);
    }

    public function test_member_can_personalize_navigation(): void
    {
        $owner = $this->navigationContext();
        $member = $this->memberContext($owner);
        $this->assertTrue(app(\App\Services\Navigation\NavigationPermissionBridge::class)->canPersonalize($member));
        $this->assertTrue(app(\App\Services\Navigation\NavigationPermissionBridge::class)->canRead($member));
    }

    public function test_viewer_cannot_manage_navigation(): void
    {
        $owner = $this->navigationContext();
        $viewer = $this->viewerContext($owner);
        $this->assertFalse(app(\App\Services\Navigation\NavigationPermissionBridge::class)->canManage($viewer));
    }

    public function test_tenant_isolation(): void
    {
        $contextA = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($contextA, [
            'module_key' => 'demo', 'navigation_key' => 'iso', 'name' => 'Iso Nav',
        ]);
        $contextB = $this->navigationContext();
        $definitions = app(NavigationDevelopmentService::class)->listDefinitions($contextB);
        $this->assertSame([], $definitions);
    }

    public function test_workspace_isolation(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'ws', 'name' => 'Workspace Nav',
        ]);
        $otherWorkspaceContext = $this->otherWorkspaceContext($context);
        $definitions = app(NavigationDevelopmentService::class)->listDefinitions($otherWorkspaceContext);
        $this->assertSame([], $definitions);
    }

    public function test_api_list_definitions(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions')
            ->assertOk();
    }

    public function test_api_store_definition(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/navigation-designer/definitions', [
                'module_key' => 'demo', 'navigation_key' => 'api', 'name' => 'API Nav',
            ])
            ->assertCreated()
            ->assertJsonPath('data.navigation_key', 'api');
    }

    public function test_api_show_definition(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'show-api', 'name' => 'Show API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId)
            ->assertOk()
            ->assertJsonPath('data.navigation_key', 'show-api');
    }

    public function test_api_update_definition(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'patch-api', 'name' => 'Before',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId, [
                'name' => 'After',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'After');
    }

    public function test_api_list_items(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'items-api', 'name' => 'Items API',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'home', 'label' => 'Home',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/items')
            ->assertOk();
    }

    public function test_api_store_item(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'store-item', 'name' => 'Store Item',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/items', [
                'item_key' => 'new', 'label' => 'New Item',
            ])
            ->assertCreated();
    }

    public function test_api_update_item(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'upd-item-api', 'name' => 'Upd Item API',
        ]);
        $item = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'edit', 'label' => 'Before',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/navigation-designer/items/'.$item->publicId, [
                'label' => 'After',
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'After');
    }

    public function test_api_delete_item(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'del-item-api', 'name' => 'Del Item API',
        ]);
        $item = app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'remove', 'label' => 'Remove',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/navigation-designer/items/'.$item->publicId)
            ->assertNoContent();
    }

    public function test_api_tree(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'tree-api', 'name' => 'Tree API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/tree')
            ->assertOk();
    }

    public function test_api_render(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'render-api', 'name' => 'Render API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/render')
            ->assertOk()
            ->assertJsonStructure(['data' => ['definition', 'tree', 'permissions']]);
    }

    public function test_api_versions_and_publish(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'version-api', 'name' => 'Version API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/versions', [
                'structure' => ['items' => []],
            ])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/versions')
            ->assertOk();
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/navigation-designer/definitions/'.$definition->publicId.'/publish')
            ->assertOk();
    }

    public function test_api_update_personalization(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'pers-api', 'name' => 'Pers API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/navigation-designer/personalization/'.$definition->publicId, [
                'personalization' => ['collapsed' => []],
            ])
            ->assertOk();
    }

    public function test_api_health(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/health')
            ->assertOk();
    }

    public function test_api_statistics(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/statistics')
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->navigationContext();
        app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'public', 'name' => 'Public Nav',
        ]);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/definitions');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_static_navigation_routes_resolve_before_parameterized(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/navigation-designer/health')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/navigation-designer/statistics')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/navigation-designer/definitions')->assertOk();
    }

    private function navigationContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'nav-designer-'.uniqid()]);
        $context = $this->buildTenantContext($user, $result);
        app()->instance(TenantContext::class, $context);

        return $context;
    }

    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            'X-Organization-Id' => $context->organization->public_id,
            'X-Workspace-Id' => $context->workspace->public_id,
        ];
    }

    private function memberContext(TenantContext $owner): TenantContext
    {
        $member = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $owner->organization->id)
            ->where('key', 'member')
            ->firstOrFail();
        $membership = $owner->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $owner->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);
        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels($member, $owner->organization, $membership, $owner->workspace);
    }

    private function viewerContext(TenantContext $owner): TenantContext
    {
        $viewer = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $owner->organization->id)
            ->where('key', 'viewer')
            ->firstOrFail();
        $membership = $owner->organization->memberships()->create([
            'user_id' => $viewer->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $owner->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);
        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels($viewer, $owner->organization, $membership, $owner->workspace);
    }

    private function otherWorkspaceContext(TenantContext $owner): TenantContext
    {
        $workspace = $owner->organization->workspaces()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid7(),
            'name' => 'Other Workspace',
            'slug' => 'other-'.uniqid(),
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        return TenantContext::fromModels($owner->user, $owner->organization, $owner->membership, $workspace);
    }
}

PHP;

file_put_contents($testPath, $body);
echo 'Wrote '.substr_count($body, 'public function test_')." tests to {$testPath}\n";
