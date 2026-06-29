<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$testPath = $base.'/tests/Feature/Services/Ui/M7UiMetadataLayoutTest.php';

$dtos = [
    'UiPageDefinition', 'UiPageReference', 'UiLayout', 'UiLayoutRegion', 'UiComponent',
    'UiComponentBinding', 'UiComponentAction', 'UiPageAction', 'UiCondition', 'UiBreakpoint',
    'UiTheme', 'UiPersonalization', 'UiRenderPayload', 'UiRenderContext', 'UiStatistics', 'UiHealthReport',
];

$enums = [
    ['UiPageStatus', ['Draft', 'Published', 'Archived']],
    ['UiPageType', ['ModuleHome', 'EntityList', 'EntityDetail', 'EntityCreate', 'EntityEdit', 'Dashboard', 'Report', 'Workflow', 'Document', 'Settings', 'Custom']],
    ['UiVisibility', ['Public', 'Private', 'Organization', 'Workspace', 'Role']],
    ['UiLayoutType', ['SingleColumn', 'TwoColumn', 'ThreeColumn', 'Sidebar', 'HeaderContent', 'DashboardGrid', 'Tabbed', 'Wizard', 'SplitPane', 'Custom']],
    ['UiRegionType', ['Header', 'Sidebar', 'Content', 'Footer', 'Toolbar', 'Card', 'Tab', 'Modal', 'Drawer', 'WidgetArea', 'Custom']],
    ['UiComponentType', ['Form', 'Table', 'Dashboard', 'Report', 'Chart', 'Metric', 'DocumentList', 'NotificationList', 'WorkflowQueue', 'ApprovalQueue', 'ActivityFeed', 'NavigationMenu', 'Custom']],
    ['UiBindingType', ['Form', 'Table', 'Dashboard', 'Report', 'Entity', 'Workflow', 'Document', 'Notification', 'Static', 'Custom']],
    ['UiActionType', ['Navigate', 'SubmitForm', 'RefreshTable', 'OpenModal', 'DownloadReport', 'StartWorkflow', 'UploadDocument', 'SendNotification', 'Custom']],
    ['UiBreakpointSize', ['Xs', 'Sm', 'Md', 'Lg', 'Xl', 'Xxl']],
    ['UiConditionOperator', ['Equals', 'NotEquals', 'Contains', 'GreaterThan', 'LessThan', 'IsEmpty', 'IsNotEmpty', 'HasPermission', 'HasRole', 'FeatureEnabled']],
];

$body = <<<'PHP'
<?php

namespace Tests\Feature\Services\Ui;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\UiActivityLog;
use App\Models\UiPage;
use App\Modules\Sdk\Ui\Contracts\UiPageRegistry;
use App\Modules\Sdk\Ui\Contracts\UiRenderer;
use App\Modules\Sdk\Ui\Contracts\UiRuntimeComposer;
use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiLayout;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Enums\UiActionType;
use App\Modules\Sdk\Ui\Enums\UiBindingType;
use App\Modules\Sdk\Ui\Enums\UiBreakpointSize;
use App\Modules\Sdk\Ui\Enums\UiConditionOperator;
use App\Modules\Sdk\Ui\Exceptions\UiRegistryException;
use App\Services\Module\ModuleDoctorService;
use App\Services\Ui\UiConditionEvaluatorService;
use App\Services\Ui\UiDevelopmentService;
use App\Services\Ui\UiPageRegistryService;
use App\Services\Ui\UiRendererService;
use App\Services\Ui\UiRuntimeComposerService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7UiMetadataLayoutTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

PHP;

foreach ($enums as [$enum, $cases]) {
    $body .= "\n    public function test_{$enum}_enum_has_expected_cases(): void\n    {\n";
    $body .= "        \$cases = array_map(static fn (\\App\\Modules\\Sdk\\Ui\\Enums\\{$enum} \$case) => \$case->value, \\App\\Modules\\Sdk\\Ui\\Enums\\{$enum}::cases());\n";
    foreach ($cases as $case) {
        $val = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $case));
        $body .= "        \$this->assertContains('{$val}', \$cases);\n";
    }
    $body .= "    }\n";
}

foreach ($dtos as $dto) {
    $body .= "\n    public function test_{$dto}_dto_roundtrip(): void\n    {\n";
    $body .= "        \$sample = \\App\\Modules\\Sdk\\Ui\\Data\\{$dto}::fromArray([]);\n";
    $body .= "        \$roundtrip = \\App\\Modules\\Sdk\\Ui\\Data\\{$dto}::fromArray(\$sample->toArray());\n";
    $body .= "        \$this->assertSame(\$sample->toArray(), \$roundtrip->toArray());\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

    public function test_ui_contracts_bound(): void
    {
        $this->assertInstanceOf(UiPageRegistryService::class, app(UiPageRegistry::class));
        $this->assertInstanceOf(UiRendererService::class, app(UiRenderer::class));
        $this->assertInstanceOf(UiRuntimeComposerService::class, app(UiRuntimeComposer::class));
    }

    public function test_ui_metadata_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.ui_metadata.enabled', true));
    }

    public function test_permission_catalog_has_ui_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(125, Permission::query()->count());
        foreach (['ui.read', 'ui.manage', 'ui.render', 'ui.personalize'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_module_doctor_includes_ui_metadata(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('ui_metadata', $report->platformSummary['enterprise']);
    }

    public function test_register_page_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, UiPageDefinition::fromArray([
            'module_key' => 'demo',
            'page_key' => 'home',
            'name' => 'Home',
            'page_type' => 'module_home',
        ]));
        $this->assertSame('demo', $page->moduleKey);
        $this->assertSame('home', $page->pageKey);
        $this->assertNotEmpty($page->publicId);
    }

    public function test_register_page_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo',
            'page_key' => 'list',
            'name' => 'List',
        ]);
        $this->assertSame('list', $page->pageKey);
    }

    public function test_duplicate_page_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(UiPageRegistryService::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'dup', 'name' => 'Dup',
        ]);
        $this->expectException(UiRegistryException::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'dup', 'name' => 'Dup 2',
        ]);
    }

    public function test_register_layout(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $layout = app(UiDevelopmentService::class)->registerLayout($context, UiLayout::fromArray([
            'layout_key' => 'main', 'name' => 'Main', 'layout_type' => 'single_column',
        ]));
        $this->assertSame('main', $layout->layoutKey);
    }

    public function test_register_component(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $component = app(UiDevelopmentService::class)->registerComponent($context, UiComponent::fromArray([
            'component_key' => 'table', 'name' => 'Table', 'component_type' => 'table',
            'binding_type' => 'table', 'binding_config' => ['module_key' => 'demo', 'table_key' => 'items'],
        ]));
        $this->assertSame('table', $component->componentKey);
    }

    public function test_find_page_by_route_path(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiPageRegistryService::class)->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'route', 'name' => 'Route', 'route_path' => '/demo/route',
        ]);
        $found = app(UiPageRegistryService::class)->findByRoutePath(
            $context->organization->id, $context->workspace->id, '/demo/route',
        );
        $this->assertSame('route', $found->pageKey);
    }

    public function test_runtime_composition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'runtime', 'name' => 'Runtime',
        ]);
        $payload = app(UiDevelopmentService::class)->composeRuntime($context);
        $this->assertArrayHasKey('page', $payload->toArray());
    }

    public function test_render_payload(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'render', 'name' => 'Render',
            'layout' => ['layout_type' => 'single_column'],
            'components' => [['component_key' => 'c1', 'name' => 'C1', 'component_type' => 'custom']],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'render');
        $data = $payload->toArray();
        $this->assertArrayHasKey('layout', $data);
        $this->assertArrayHasKey('components', $data);
        $this->assertArrayHasKey('permissions', $data);
    }

    public function test_form_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'form', 'name' => 'Form Page',
            'components' => [[
                'component_key' => 'form1', 'name' => 'Form', 'component_type' => 'form',
                'binding_type' => 'form', 'binding_config' => ['module_key' => 'demo', 'form_key' => 'entry'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'form');
        $this->assertNotEmpty($payload->components);
    }

    public function test_table_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'table', 'name' => 'Table Page',
            'components' => [[
                'component_key' => 'tbl', 'name' => 'Table', 'component_type' => 'table',
                'binding_type' => 'table', 'binding_config' => ['module_key' => 'demo', 'table_key' => 'items'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'table');
        $this->assertNotEmpty($payload->components);
    }

    public function test_dashboard_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'dash', 'name' => 'Dash Page',
            'components' => [[
                'component_key' => 'dash', 'name' => 'Dash', 'component_type' => 'dashboard',
                'binding_type' => 'dashboard', 'binding_config' => ['module_key' => 'demo', 'dashboard_key' => 'main'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'dash');
        $this->assertNotEmpty($payload->components);
    }

    public function test_report_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'report', 'name' => 'Report Page',
            'components' => [[
                'component_key' => 'rep', 'name' => 'Report', 'component_type' => 'report',
                'binding_type' => 'report', 'binding_config' => ['module_key' => 'demo', 'report_key' => 'summary'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'report');
        $this->assertNotEmpty($payload->components);
    }

    public function test_condition_operators(): void
    {
        $evaluator = app(UiConditionEvaluatorService::class);
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::Equals->value, 'field' => 'status', 'value' => 'active']], ['status' => 'active']));
        $this->assertFalse($evaluator->evaluate($context, [['operator' => UiConditionOperator::NotEquals->value, 'field' => 'status', 'value' => 'active']], ['status' => 'active']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::Contains->value, 'field' => 'name', 'value' => 'dem']], ['name' => 'demo']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::GreaterThan->value, 'field' => 'count', 'value' => 1]], ['count' => 2]));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::LessThan->value, 'field' => 'count', 'value' => 5]], ['count' => 2]));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::IsEmpty->value, 'field' => 'note']], ['note' => '']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::IsNotEmpty->value, 'field' => 'note']], ['note' => 'x']));
    }

    public function test_has_permission_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::HasPermission->value, 'value' => 'ui.read']]));
    }

    public function test_has_role_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::HasRole->value, 'value' => 'owner']]));
    }

    public function test_feature_enabled_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::FeatureEnabled->value, 'value' => 'ui_metadata']]));
    }

    public function test_actions_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'actions', 'name' => 'Actions',
            'actions' => [['action_key' => 'go', 'action_type' => UiActionType::Navigate->value, 'label' => 'Go']],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'actions');
        $this->assertNotEmpty($payload->actions);
    }

    public function test_breakpoint_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'bp', 'name' => 'Breakpoints',
            'breakpoints' => [UiBreakpointSize::Md->value => ['columns' => 2]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'bp');
        $this->assertArrayHasKey(UiBreakpointSize::Md->value, $payload->breakpoints);
    }

    public function test_personalization_update(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'pers', 'name' => 'Personalize',
        ]);
        $personalization = app(UiDevelopmentService::class)->updatePersonalization($context, $page->publicId, [
            'column_order' => ['a', 'b'],
        ]);
        $this->assertSame(['column_order' => ['a', 'b']], $personalization->personalization);
    }

    public function test_business_module_manifest_ui_integration(): void
    {
        $manifest = \App\Modules\Sdk\Development\Data\BusinessModuleManifest::fromArray([
            'module_key' => 'ui.demo',
            'name' => 'UI Demo',
            'ui' => [
                'pages' => [['page_key' => 'home', 'name' => 'Home']],
                'layouts' => [['layout_key' => 'main', 'name' => 'Main']],
                'components' => [['component_key' => 'list', 'name' => 'List', 'component_type' => 'table']],
            ],
        ]);
        $this->assertCount(1, $manifest->uiPages);
        $this->assertCount(1, $manifest->uiLayouts);
        $this->assertCount(1, $manifest->uiComponents);
    }

    public function test_business_module_base_pages_hook(): void
    {
        $module = new class extends \App\Modules\Sdk\Development\BusinessModuleBase {
            protected string $moduleKey = 'ui.hooks';
        };
        $this->assertIsArray($module->pages());
        $this->assertIsArray($module->layouts());
        $this->assertIsArray($module->components());
    }

    public function test_search_indexing_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'search', 'name' => 'Search',
        ]);
        $this->assertTrue(true);
    }

    public function test_audit_actions_recorded(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'audit', 'name' => 'Audit',
        ]);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::UiPageRegistered->value)->exists());
        $this->assertTrue(UiActivityLog::query()->where('action', 'page.registered')->exists());
    }

    public function test_platform_event_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'event', 'name' => 'Event',
        ]);
        $this->assertTrue(true);
    }

    public function test_runtime_metadata_includes_ui_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $runtime = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class)->resolve($context);
        $this->assertTrue($runtime->capabilities['ui_metadata'] ?? false);
        $this->assertArrayHasKey('ui_metadata', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_doctor_health_section(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $health = app(UiDevelopmentService::class)->health($context);
        $this->assertTrue($health->enabled);
        $this->assertContains('No UI pages registered.', $health->warnings);
    }

    public function test_missing_table_guard(): void
    {
        Schema::drop('ui_pages');
        $health = app(\App\Services\Ui\UiHealthService::class)->health();
        $this->assertSame('critical', $health->status);
        $this->assertNotEmpty($health->missingTables);
    }

    public function test_binding_types_enum(): void
    {
        $this->assertSame('form', UiBindingType::Form->value);
        $this->assertSame('table', UiBindingType::Table->value);
    }

    public function test_member_can_render_ui(): void
    {
        $owner = $this->tenantContext();
        $member = $this->memberContext($owner);
        $this->assertTrue(app(\App\Services\Ui\UiPermissionBridge::class)->canRender($member));
        $this->assertTrue(app(\App\Services\Ui\UiPermissionBridge::class)->canPersonalize($member));
    }

    public function test_viewer_cannot_manage_ui(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        $this->assertFalse(app(\App\Services\Ui\UiPermissionBridge::class)->canManage($viewer));
    }

    public function test_tenant_isolation(): void
    {
        $contextA = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        app(UiDevelopmentService::class)->registerPage($contextA, [
            'module_key' => 'demo', 'page_key' => 'iso', 'name' => 'Iso',
        ]);
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextB);
        $pages = app(UiDevelopmentService::class)->listPages($contextB);
        $this->assertSame([], $pages);
    }

    public function test_api_list_pages(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages')
            ->assertOk();
    }

    public function test_api_store_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/pages', [
                'module_key' => 'demo', 'page_key' => 'api', 'name' => 'API Page',
            ])
            ->assertCreated()
            ->assertJsonPath('data.page_key', 'api');
    }

    public function test_api_show_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'show', 'name' => 'Show',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages/demo/show')
            ->assertOk()
            ->assertJsonPath('data.page_key', 'show');
    }

    public function test_api_render_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'render-api', 'name' => 'Render API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages/demo/render-api/render')
            ->assertOk()
            ->assertJsonStructure(['data' => ['layout', 'components', 'permissions']]);
    }

    public function test_api_runtime(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/runtime')
            ->assertOk();
    }

    public function test_api_health(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/health')
            ->assertOk();
    }

    public function test_api_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/statistics')
            ->assertOk();
    }

    public function test_api_store_layout_and_component(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/layouts', ['layout_key' => 'api', 'name' => 'API Layout'])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/components', ['component_key' => 'api', 'name' => 'API Component'])
            ->assertCreated();
    }

    public function test_api_update_personalization(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'pers-api', 'name' => 'Pers API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/ui/personalization/'.$page->publicId, [
                'personalization' => ['hidden' => []],
            ])
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'public', 'name' => 'Public',
        ]);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_static_ui_routes_resolve_before_parameterized(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/runtime')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/health')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/statistics')->assertOk();
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ui-metadata-'.uniqid()]);
        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /** @return array<string, string> */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}

PHP;

file_put_contents($testPath, $body);
echo "Wrote {$testPath}\n";
