<?php

namespace Tests\Feature\Services\Dashboard;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Models\DashboardView as DashboardViewModel;
use App\Models\DashboardWidget as DashboardWidgetModel;
use App\Models\Permission;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardFilter;
use App\Modules\Sdk\Dashboard\Data\DashboardHealthReport;
use App\Modules\Sdk\Dashboard\Data\DashboardStatistics;
use App\Modules\Sdk\Dashboard\Data\DashboardView;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Enums\DashboardFilterOperator;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardRegistryException;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardValidationException;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Services\Dashboard\DynamicDashboardDataProviderService;
use App\Services\Dashboard\DynamicDashboardDevelopmentService;
use App\Services\Dashboard\DynamicDashboardFilterService;
use App\Services\Dashboard\DynamicDashboardGeneratorService;
use App\Services\Dashboard\DynamicDashboardHealthService;
use App\Services\Dashboard\DynamicDashboardMapper;
use App\Services\Dashboard\DynamicDashboardRegistryService;
use App\Services\Dashboard\DynamicDashboardRendererService;
use App\Services\Dashboard\DynamicDashboardStatisticsService;
use App\Services\Dashboard\DynamicDashboardValidationService;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5DynamicDashboardFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_dashboard_definition_dto_roundtrip(): void
    {
        $definition = DashboardDefinition::fromArray($this->sampleDashboardDefinition('crm.core', 'lead_dashboard'));

        $roundtrip = DashboardDefinition::fromArray($definition->toArray());

        $this->assertSame('crm.core', $roundtrip->moduleKey);
        $this->assertSame('lead_dashboard', $roundtrip->dashboardKey);
        $this->assertSame('Lead Dashboard', $roundtrip->name);
    }

    public function test_dashboard_widget_data_dto_serializes(): void
    {
        $data = \App\Modules\Sdk\Dashboard\Data\DashboardWidgetData::fromArray([
            'widget_key' => 'total_records',
            'value' => 42,
            'metadata' => ['placeholder' => true],
        ]);

        $this->assertSame(42, $data->toArray()['value']);
        $this->assertSame('total_records', $data->jsonSerialize()['widget_key']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new DashboardHealthReport(
            enabled: true,
            definitions: 2,
            widgets: 5,
            views: 1,
            warnings: ['No dashboard definitions are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = DashboardStatistics::fromArray([
            'definitions' => 2,
            'widgets' => 8,
            'views' => 3,
            'registered_modules' => ['crm.core'],
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['definitions']);
        $this->assertSame(['crm.core'], $statistics->registeredModules);
    }

    public function test_dashboard_view_dto_serializes_public_id(): void
    {
        $view = new DashboardView(
            name: 'Default',
            publicId: '01900000-0000-7000-8000-000000000801',
        );

        $payload = $view->toArray();

        $this->assertArrayHasKey('public_id', $payload);
        $this->assertSame('01900000-0000-7000-8000-000000000801', $payload['public_id']);
    }

    public function test_validator_accepts_valid_definition(): void
    {
        app(DynamicDashboardValidationService::class)->assertValid(
            DashboardDefinition::fromArray($this->sampleDashboardDefinition('procurement.core', 'supplier_dashboard')),
        );

        $this->assertTrue(true);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleDashboardDefinition('INVALID KEY', 'record_dashboard');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(DashboardValidationException::class);
        app(DynamicDashboardValidationService::class)->assertValid(DashboardDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_name(): void
    {
        $data = $this->sampleDashboardDefinition('finance.core', 'invoice_dashboard');
        $data['name'] = '';

        $this->expectException(DashboardValidationException::class);
        app(DynamicDashboardValidationService::class)->assertValid(DashboardDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_dashboard_key(): void
    {
        $data = $this->sampleDashboardDefinition('finance.core', '');
        $data['dashboard_key'] = '';

        $this->expectException(DashboardValidationException::class);
        app(DynamicDashboardValidationService::class)->assertValid(DashboardDefinition::fromArray($data));
    }

    public function test_registry_registers_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicDashboardRegistryService::class)->register(
            DashboardDefinition::fromArray($this->sampleDashboardDefinition('registry.dto.'.uniqid(), 'record_dashboard')),
        );

        $this->assertNotEmpty($definition->publicId);
        $this->assertTrue(DashboardDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists());
    }

    public function test_registry_registers_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicDashboardRegistryService::class)->register(
            $this->sampleDashboardDefinition('registry.array.'.uniqid(), 'record_dashboard'),
        );

        $this->assertSame('record_dashboard', $definition->dashboardKey);
    }

    public function test_registry_duplicate_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $payload = $this->sampleDashboardDefinition('registry.dup.'.uniqid(), 'record_dashboard');

        app(DynamicDashboardRegistryService::class)->register($payload);

        $this->expectException(DashboardRegistryException::class);
        app(DynamicDashboardRegistryService::class)->register($payload);
    }

    public function test_registry_list_and_find(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.list.'.uniqid();

        app(DynamicDashboardRegistryService::class)->register(
            $this->sampleDashboardDefinition($moduleKey, 'order_dashboard'),
        );

        $found = app(DynamicDashboardRegistryService::class)->find($moduleKey, 'order_dashboard');
        $listed = app(DynamicDashboardRegistryService::class)->list($moduleKey);

        $this->assertNotNull($found);
        $this->assertCount(1, $listed);
    }

    public function test_registry_manifest_dashboards(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.manifest.'.uniqid();

        $registered = app(DynamicDashboardRegistryService::class)->registerFromManifestDashboards([
            [
                'dashboard_key' => 'sales_dashboard',
                'name' => 'Sales Dashboard',
            ],
        ], $moduleKey);

        $this->assertCount(1, $registered);
        $this->assertSame('sales_dashboard', $registered[0]->dashboardKey);
    }

    public function test_registry_find_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicDashboardRegistryService::class)->register(
            $this->sampleDashboardDefinition('registry.public.'.uniqid(), 'record_dashboard'),
        );

        $found = app(DynamicDashboardRegistryService::class)->findByPublicId((string) $definition->publicId);

        $this->assertNotNull($found);
        $this->assertSame($definition->dashboardKey, $found->dashboardKey);
    }

    public function test_registry_find_by_entity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.entity.'.uniqid();

        $payload = $this->sampleDashboardDefinition($moduleKey, 'lead_dashboard');
        $payload['entity_key'] = 'lead';
        app(DynamicDashboardRegistryService::class)->register($payload);

        $found = app(DynamicDashboardRegistryService::class)->findByEntity($moduleKey, 'lead');

        $this->assertCount(1, $found);
        $this->assertSame('lead', $found[0]->entityKey);
    }

    public function test_mapper_to_reference_returns_public_id_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicDashboardRegistryService::class)->register(
            $this->sampleDashboardDefinition('mapper.ref.'.uniqid(), 'record_dashboard'),
        );

        $model = DashboardDefinitionModel::query()->where('public_id', $definition->publicId)->firstOrFail();
        $reference = DynamicDashboardMapper::toReference($model);

        $this->assertArrayHasKey('public_id', $reference);
        $this->assertArrayNotHasKey('id', $reference);
    }

    public function test_generator_creates_entity_dashboard(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'customer'),
        );

        $dashboard = app(DynamicDashboardGeneratorService::class)->generateEntityDashboard($moduleKey, 'customer');

        $this->assertSame('customer_dashboard', $dashboard->dashboardKey);
        $this->assertNotEmpty($dashboard->widgets);
        $this->assertTrue($dashboard->metadata['generated_from_entity'] ?? false);
    }

    public function test_generator_includes_total_records_widget(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.kpi.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'order'),
        );

        $dashboard = app(DynamicDashboardGeneratorService::class)->generateEntityDashboard($moduleKey, 'order');
        $keys = array_map(fn (DashboardWidget $w) => $w->widgetKey, $dashboard->widgets);

        $this->assertContains('total_records', $keys);
        $this->assertContains('recent_activity', $keys);
    }

    public function test_generator_includes_placeholder_widgets(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.placeholder.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'task'),
        );

        $dashboard = app(DynamicDashboardGeneratorService::class)->generateEntityDashboard($moduleKey, 'task');
        $keys = array_map(fn (DashboardWidget $w) => $w->widgetKey, $dashboard->widgets);

        $this->assertContains('workflow_queue', $keys);
        $this->assertContains('approval_queue', $keys);
    }

    public function test_renderer_returns_structure_not_html(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $payload = app(DynamicDashboardRendererService::class)->render($definition);

        $this->assertArrayHasKey('metadata', $payload);
        $this->assertArrayHasKey('widgets', $payload);
        $this->assertArrayHasKey('widget_data', $payload);
        $this->assertArrayHasKey('layout', $payload);
        $this->assertArrayNotHasKey('html', $payload);
    }

    public function test_data_provider_entity_count_placeholder(): void
    {
        $widget = DashboardWidget::fromArray([
            'widget_key' => 'total_records',
            'name' => 'Total Records',
            'data_source_type' => 'entity_count',
            'data_source_config' => ['module_key' => 'crm.core', 'entity_key' => 'lead'],
        ]);

        $data = app(DynamicDashboardDataProviderService::class)->resolve($widget);

        $this->assertTrue($data->metadata['placeholder'] ?? false);
        $this->assertSame('entity_count', $data->metadata['source']);
    }

    public function test_data_provider_static_source(): void
    {
        $widget = DashboardWidget::fromArray([
            'widget_key' => 'static_kpi',
            'name' => 'Static KPI',
            'data_source_type' => 'static',
            'data_source_config' => ['value' => 100],
        ]);

        $data = app(DynamicDashboardDataProviderService::class)->resolve($widget);

        $this->assertSame(100, $data->value);
    }

    public function test_data_provider_types_are_defined(): void
    {
        $types = DynamicDashboardDataProviderService::DATA_SOURCE_TYPES;

        $this->assertContains('entity_count', $types);
        $this->assertContains('activity_placeholder', $types);
        $this->assertContains('table_placeholder', $types);
    }

    public function test_filter_evaluator_equals(): void
    {
        $filter = new DashboardFilter(fieldKey: 'status', operator: DashboardFilterOperator::Equals->value, value: 'active');
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'active'));
        $this->assertFalse($evaluator->evaluate($filter, 'inactive'));
    }

    public function test_filter_evaluator_contains(): void
    {
        $filter = new DashboardFilter(fieldKey: 'name', operator: DashboardFilterOperator::Contains->value, value: 'acme');
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'Acme Corp'));
    }

    public function test_filter_evaluator_greater_than(): void
    {
        $filter = new DashboardFilter(fieldKey: 'amount', operator: DashboardFilterOperator::GreaterThan->value, value: 10);
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 15));
        $this->assertFalse($evaluator->evaluate($filter, 5));
    }

    public function test_filter_evaluator_between(): void
    {
        $filter = new DashboardFilter(fieldKey: 'score', operator: DashboardFilterOperator::Between->value, value: [10, 20]);
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 15));
        $this->assertFalse($evaluator->evaluate($filter, 25));
    }

    public function test_filter_evaluator_is_empty(): void
    {
        $filter = new DashboardFilter(fieldKey: 'notes', operator: DashboardFilterOperator::IsEmpty->value);
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, null));
        $this->assertTrue($evaluator->evaluate($filter, ''));
    }

    public function test_filter_evaluator_is_not_empty(): void
    {
        $filter = new DashboardFilter(fieldKey: 'notes', operator: DashboardFilterOperator::IsNotEmpty->value);
        $evaluator = app(DynamicDashboardFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'hello'));
    }

    public function test_widget_create_update_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $widget = app(DynamicDashboardDevelopmentService::class)->createWidget(
            $context,
            $definition->moduleKey,
            $definition->dashboardKey,
            DashboardWidget::fromArray([
                'widget_key' => 'custom_kpi',
                'name' => 'Custom KPI',
                'widget_type' => 'kpi_card',
                'data_source_type' => 'static',
            ]),
        );

        $this->assertNotEmpty($widget->publicId);

        $updated = app(DynamicDashboardDevelopmentService::class)->updateWidget(
            $context,
            DashboardWidget::fromArray(array_merge($widget->toArray(), [
                'name' => 'Updated KPI',
            ])),
        );

        $this->assertSame('Updated KPI', $updated->name);

        app(DynamicDashboardDevelopmentService::class)->deleteWidgetByPublicId($context, (string) $widget->publicId);

        $this->assertFalse(DashboardWidgetModel::query()->where('public_id', $widget->publicId)->exists());
    }

    public function test_views_save_list_and_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $view = app(DynamicDashboardDevelopmentService::class)->saveView(
            $context,
            $definition->moduleKey,
            $definition->dashboardKey,
            DashboardView::fromArray(['name' => 'Executive View']),
        );

        $views = app(DynamicDashboardDevelopmentService::class)->listViews(
            $context,
            $definition->moduleKey,
            $definition->dashboardKey,
        );

        $this->assertCount(1, $views);
        $this->assertSame('Executive View', $views[0]->name);

        app(DynamicDashboardDevelopmentService::class)->deleteViewByPublicId($context, (string) $view->publicId);

        $this->assertFalse(DashboardViewModel::query()->where('public_id', $view->publicId)->exists());
    }

    public function test_set_default_view(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $view = app(DynamicDashboardDevelopmentService::class)->saveView(
            $context,
            $definition->moduleKey,
            $definition->dashboardKey,
            DashboardView::fromArray(['name' => 'Default View', 'is_default' => true]),
        );

        $this->assertTrue($view->isDefault);
    }

    public function test_activity_logging_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $entries = app(DynamicDashboardDevelopmentService::class)->listActivity(
            $context,
            (string) $definition->publicId,
        );

        $this->assertIsArray($entries);
    }

    public function test_health_service_warnings_when_empty(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $report = app(DynamicDashboardHealthService::class)->health($context);

        $this->assertTrue($report->enabled);
        $this->assertContains('No dashboard definitions are registered yet.', $report->warnings);
    }

    public function test_statistics_counts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleDashboard($context);

        $stats = app(DynamicDashboardStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertGreaterThanOrEqual(1, $stats->definitions);
    }

    public function test_development_service_list_show_render(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $listed = app(DynamicDashboardDevelopmentService::class)->listDefinitions($context);
        $shown = app(DynamicDashboardDevelopmentService::class)->showDefinition(
            $context,
            $definition->moduleKey,
            $definition->dashboardKey,
        );
        $rendered = app(DynamicDashboardDevelopmentService::class)->renderDashboard($context, $shown);

        $this->assertNotEmpty($listed);
        $this->assertSame($definition->dashboardKey, $shown->dashboardKey);
        $this->assertArrayHasKey('widget_data', $rendered);
    }

    public function test_development_service_health_and_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicDashboardDevelopmentService::class)->health($context);
        $statistics = app(DynamicDashboardDevelopmentService::class)->statistics($context);

        $this->assertInstanceOf(DashboardHealthReport::class, $health);
        $this->assertInstanceOf(DashboardStatistics::class, $statistics);
    }

    public function test_api_index_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleDashboard($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/dashboards');

        $response->assertOk();
    }

    public function test_api_show_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey);

        $response->assertOk()->assertJsonPath('data.dashboard_key', $definition->dashboardKey);
    }

    public function test_api_render_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/render');

        $response->assertOk()->assertJsonStructure(['data' => ['metadata', 'widgets', 'widget_data']]);
    }

    public function test_api_widget_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $create = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/widgets', [
                'widget_key' => 'api_kpi',
                'name' => 'API KPI',
                'widget_type' => 'kpi_card',
            ]);

        $create->assertCreated();

        $list = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/widgets');

        $list->assertOk();
    }

    public function test_api_view_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        $create = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/views', [
                'name' => 'API View',
            ]);

        $create->assertCreated();
        $viewPublicId = $create->json('data.public_id');

        $this->withHeaders($this->tenantHeaders($context))
            ->delete('/api/v1/tenant/dashboards/views/'.$viewPublicId)
            ->assertNoContent();
    }

    public function test_permission_catalog_includes_dashboard_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'dashboards.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'dashboards.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'dashboards.render')->exists());
        $this->assertTrue(Permission::query()->where('key', 'dashboards.export')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_member_can_render(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDashboard($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->getJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/render');

        $response->assertOk();
    }

    public function test_viewer_cannot_render(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDashboard($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->getJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/render');

        $response->assertForbidden();
    }

    public function test_viewer_cannot_save_view(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDashboard($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/dashboards/'.$definition->moduleKey.'/'.$definition->dashboardKey.'/views', [
                'name' => 'Viewer View',
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_isolation_for_views(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        $definition = $this->registerSampleDashboard($contextA);

        $view = app(DynamicDashboardDevelopmentService::class)->saveView(
            $contextA,
            $definition->moduleKey,
            $definition->dashboardKey,
            DashboardView::fromArray(['name' => 'Tenant A View']),
        );

        app()->instance(TenantContext::class, $contextB);

        $this->expectException(\App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException::class);
        app(DynamicDashboardDevelopmentService::class)->deleteViewByPublicId($contextB, (string) $view->publicId);
    }

    public function test_module_doctor_includes_dashboards_health(): void
    {
        $this->seedHeosPlatform();

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('dashboards', $report->platformSummary['enterprise']);
    }

    public function test_workspace_runtime_includes_dashboards(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['dashboards'] ?? false);
        $this->assertArrayHasKey('dashboards', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_config_dashboards_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.dashboards.enabled', true));
    }

    public function test_audit_action_recorded_on_render(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDashboard($context);

        app(DynamicDashboardDevelopmentService::class)->renderDashboard($context, $definition);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DashboardRendered->value)->exists());
    }

    public function test_business_module_base_dashboards_integration(): void
    {
        $module = new class extends BusinessModuleBase
        {
            protected string $moduleKey = 'demo.dashboards';

            public function dashboards(): array
            {
                return [[
                    'dashboard_key' => 'customer_dashboard',
                    'name' => 'Customer Dashboard',
                ]];
            }
        };

        $this->assertCount(1, $module->dashboards());
        $this->assertSame('customer_dashboard', $module->dashboards()[0]['dashboard_key']);
    }

    public function test_business_module_installer_registers_manifest_dashboards(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'dashboard.install.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray([
                'module_key' => $moduleKey,
                'name' => 'Dashboard Install Module',
                'version' => '1.0.0',
                'permissions' => [[
                    'key' => $moduleKey.'.records.read',
                    'name' => 'Read Records',
                    'domain' => 'business',
                ]],
                'routes' => [[
                    'name' => $moduleKey.'.records.index',
                    'method' => 'GET',
                    'uri' => '/records',
                    'action' => 'index',
                ]],
                'dashboards' => [[
                    'dashboard_key' => 'order_dashboard',
                    'name' => 'Order Dashboard',
                ]],
                'dependencies' => ['heos.core'],
            ]),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('dashboard_key', 'order_dashboard')
            ->exists());
    }

    public function test_missing_dashboard_guard(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->expectException(\App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException::class);
        app(DynamicDashboardDevelopmentService::class)->showDefinition($context, 'missing.module', 'missing_dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleDashboardDefinition(string $moduleKey, string $dashboardKey): array
    {
        return [
            'module_key' => $moduleKey,
            'dashboard_key' => $dashboardKey,
            'name' => $dashboardKey === '' ? '' : ucwords(str_replace(['.', '-', '_'], ' ', $dashboardKey)),
            'description' => 'Sample dashboard definition.',
            'type' => 'entity',
            'status' => 'registered',
            'visibility' => 'organization',
            'widgets' => [[
                'widget_key' => 'total_records',
                'name' => 'Total Records',
                'widget_type' => 'kpi_card',
                'data_source_type' => 'entity_count',
            ]],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleEntityDefinition(string $moduleKey, string $entityKey): array
    {
        return [
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'name' => ucwords(str_replace(['.', '-', '_'], ' ', $entityKey)),
            'description' => 'Sample entity definition.',
            'status' => 'registered',
            'visibility' => 'organization',
            'ownership_scope' => 'organization',
            'capabilities' => ['searchable' => true],
            'fields' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
                'searchable' => true,
            ]],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'dynamic-dashboards-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function registerSampleDashboard(TenantContext $context): DashboardDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(DynamicDashboardDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleDashboardDefinition('sample.dashboards.'.uniqid(), 'record_dashboard'),
        );
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

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}
