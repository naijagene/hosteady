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

    public function test_NavigationType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationType $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationType::cases());
        $this->assertContains('primary', $cases);
        $this->assertContains('sidebar', $cases);
        $this->assertContains('topbar', $cases);
        $this->assertContains('footer', $cases);
        $this->assertContains('mobile', $cases);
        $this->assertContains('breadcrumb', $cases);
        $this->assertContains('contextual', $cases);
        $this->assertContains('command_palette', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_NavigationItemType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationItemType $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationItemType::cases());
        $this->assertContains('link', $cases);
        $this->assertContains('route', $cases);
        $this->assertContains('page', $cases);
        $this->assertContains('module', $cases);
        $this->assertContains('application', $cases);
        $this->assertContains('group', $cases);
        $this->assertContains('divider', $cases);
        $this->assertContains('heading', $cases);
        $this->assertContains('external', $cases);
        $this->assertContains('action', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_NavigationVisibility_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationVisibility $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationVisibility::cases());
        $this->assertContains('public', $cases);
        $this->assertContains('authenticated', $cases);
        $this->assertContains('permission_based', $cases);
        $this->assertContains('role_based', $cases);
        $this->assertContains('workspace_based', $cases);
        $this->assertContains('organization_based', $cases);
        $this->assertContains('hidden', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_NavigationScope_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationScope $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationScope::cases());
        $this->assertContains('organization', $cases);
        $this->assertContains('workspace', $cases);
        $this->assertContains('application', $cases);
    }

    public function test_NavigationConditionOperator_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationConditionOperator $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationConditionOperator::cases());
        $this->assertContains('equals', $cases);
        $this->assertContains('not_equals', $cases);
        $this->assertContains('contains', $cases);
        $this->assertContains('greater_than', $cases);
        $this->assertContains('less_than', $cases);
        $this->assertContains('is_empty', $cases);
        $this->assertContains('is_not_empty', $cases);
        $this->assertContains('has_permission', $cases);
        $this->assertContains('has_role', $cases);
        $this->assertContains('feature_enabled', $cases);
    }

    public function test_NavigationDefinitionStatus_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationDefinitionStatus $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationDefinitionStatus::cases());
        $this->assertContains('draft', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_NavigationVersionStatus_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Navigation\Enums\NavigationVersionStatus $case) => $case->value, \App\Modules\Sdk\Navigation\Enums\NavigationVersionStatus::cases());
        $this->assertContains('draft', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_NavigationDefinition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationTree_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationTree::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationTree::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationTreeNode_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationTreeNode::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationTreeNode::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationVersion_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationVersion::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationVersion::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationRenderPayload_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationRenderPayload::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationRenderPayload::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationPersonalization_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationPersonalization::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationPersonalization::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationStatistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationHealthReport_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationCondition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Navigation\Data\NavigationCondition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Navigation\Data\NavigationCondition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

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
        $this->assertSame(129, Permission::query()->count());
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
        $menus = app(NavigationBuilderService::class)->build($context);
        $this->assertNotEmpty($menus);
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

    public function test_item_list_does_not_crash_when_navigation_items_missing(): void
    {
        Schema::drop('navigation_items');
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'items-missing', 'name' => 'Items Missing',
        ]);
        $model = app(NavigationRegistryService::class)->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace->id,
            $definition->publicId,
        );
        $items = app(\App\Services\Navigation\NavigationItemService::class)->listForDefinition($context, $model);
        $this->assertSame([], $items);
    }

    public function test_tree_builder_returns_safe_empty_tree_when_tables_missing(): void
    {
        Schema::drop('navigation_definitions');
        Schema::drop('navigation_versions');
        Schema::drop('navigation_items');
        Schema::drop('navigation_personalizations');

        $tree = app(NavigationTreeBuilderService::class)->build([]);

        $this->assertSame([], $tree->nodes);
        $this->assertNotEmpty($tree->warnings);
        $this->assertStringContainsString('Run php artisan migrate.', $tree->warnings[0]);
    }

    public function test_renderer_returns_safe_warning_payload_when_tables_missing(): void
    {
        Schema::drop('navigation_definitions');
        Schema::drop('navigation_versions');
        Schema::drop('navigation_items');
        Schema::drop('navigation_personalizations');

        $context = $this->navigationContext();
        $payload = app(NavigationRendererService::class)->render($context, 'main', 'demo');

        $this->assertSame([], $payload->tree);
        $this->assertSame('warning', $payload->runtimeContext['status'] ?? null);
        $this->assertContains('navigation_definitions', $payload->runtimeContext['missing_tables'] ?? []);
    }

    public function test_statistics_returns_safe_zeros_when_tables_missing(): void
    {
        Schema::drop('navigation_definitions');
        Schema::drop('navigation_versions');
        Schema::drop('navigation_items');
        Schema::drop('navigation_personalizations');

        $context = $this->navigationContext();
        $stats = app(\App\Services\Navigation\NavigationStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertSame(0, $stats->definitions);
        $this->assertSame(0, $stats->versions);
        $this->assertSame(0, $stats->items);
        $this->assertSame(0, $stats->personalizations);
    }

    public function test_heos_doctor_navigation_designer_missing_table_warning_message(): void
    {
        Schema::drop('navigation_definitions');

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertTrue(collect($report->warnings)->contains(
            fn (string $warning): bool => str_contains($warning, 'enterprise.navigation_designer')
                && str_contains($warning, 'navigation_definitions')
                && str_contains($warning, 'Run php artisan migrate.'),
        ));
    }

    public function test_runtime_endpoint_returns_published_navigation_payload(): void
    {
        $context = $this->navigationContext();
        $definition = app(NavigationDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'navigation_key' => 'main', 'name' => 'Main Runtime',
        ]);
        app(NavigationDevelopmentService::class)->createItemForDefinition($context, $definition->publicId, [
            'item_key' => 'home', 'label' => 'Home', 'route' => '/home',
        ]);
        app(NavigationDevelopmentService::class)->createVersion($context, $definition->publicId, []);
        app(NavigationDevelopmentService::class)->publishDefinition($context, $definition->publicId);

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/runtime?navigation_key=main&module_key=demo')
            ->assertOk()
            ->assertJsonPath('data.source', 'navigation_designer')
            ->assertJsonStructure(['data' => ['menus', 'tree', 'runtime_context', 'permissions']]);
    }

    public function test_runtime_endpoint_falls_back_safely_when_no_published_navigation(): void
    {
        $context = $this->navigationContext();

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/runtime?navigation_key=missing&module_key=demo')
            ->assertOk();

        $this->assertSame([], $response->json('data.menus'));
        $this->assertSame('navigation_designer', $response->json('data.source'));
    }

    public function test_api_runtime(): void
    {
        $context = $this->navigationContext();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/navigation-designer/runtime')
            ->assertOk();
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
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/navigation-designer/runtime')->assertOk();
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
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
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
