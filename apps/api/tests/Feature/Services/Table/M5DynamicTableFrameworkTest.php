<?php

namespace Tests\Feature\Services\Table;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\TableDefinition as TableDefinitionModel;
use App\Models\TableView as TableViewModel;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableFilter;
use App\Modules\Sdk\Table\Data\TableHealthReport;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Data\TableStatistics;
use App\Modules\Sdk\Table\Data\TableView;
use App\Modules\Sdk\Table\Enums\TableFilterOperator;
use App\Modules\Sdk\Table\Exceptions\TableRegistryException;
use App\Modules\Sdk\Table\Exceptions\TableValidationException;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Services\Table\DynamicTableDevelopmentService;
use App\Services\Table\DynamicTableFilterEvaluator;
use App\Services\Table\DynamicTableGeneratorService;
use App\Services\Table\DynamicTableHealthService;
use App\Services\Table\DynamicTableMapper;
use App\Services\Table\DynamicTableRegistryService;
use App\Services\Table\DynamicTableRendererService;
use App\Services\Table\DynamicTableSortService;
use App\Services\Table\DynamicTableStatisticsService;
use App\Services\Table\DynamicTableValidationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5DynamicTableFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_table_definition_dto_roundtrip(): void
    {
        $definition = TableDefinition::fromArray($this->sampleTableDefinition('crm.core', 'lead_list'));

        $roundtrip = TableDefinition::fromArray($definition->toArray());

        $this->assertSame('crm.core', $roundtrip->moduleKey);
        $this->assertSame('lead_list', $roundtrip->tableKey);
        $this->assertSame('Lead List', $roundtrip->name);
    }

    public function test_table_query_result_dto_serializes(): void
    {
        $result = \App\Modules\Sdk\Table\Data\TableQueryResult::fromArray([
            'module_key' => 'crm.core',
            'table_key' => 'lead_list',
            'rows' => [['public_id' => '01900000-0000-7000-8000-000000000701', 'values' => ['name' => 'Acme']]],
            'total' => 1,
            'page' => 1,
            'per_page' => 25,
            'total_pages' => 1,
        ]);

        $this->assertSame(1, $result->toArray()['total']);
        $this->assertCount(1, $result->jsonSerialize()['rows']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new TableHealthReport(
            enabled: true,
            definitions: 2,
            views: 1,
            warnings: ['No table definitions are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = TableStatistics::fromArray([
            'definitions' => 2,
            'views' => 3,
            'registered_modules' => ['crm.core'],
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['definitions']);
        $this->assertSame(['crm.core'], $statistics->registeredModules);
    }

    public function test_table_view_dto_serializes_public_id(): void
    {
        $view = new TableView(
            moduleKey: 'crm.core',
            tableKey: 'lead_list',
            name: 'Default',
            publicId: '01900000-0000-7000-8000-000000000702',
        );

        $payload = $view->toArray();

        $this->assertArrayHasKey('public_id', $payload);
        $this->assertSame('01900000-0000-7000-8000-000000000702', $payload['public_id']);
    }

    public function test_validator_accepts_valid_definition(): void
    {
        app(DynamicTableValidationService::class)->assertValid(
            TableDefinition::fromArray($this->sampleTableDefinition('procurement.core', 'supplier_list')),
        );

        $this->assertTrue(true);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleTableDefinition('INVALID KEY', 'record_list');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(TableValidationException::class);
        app(DynamicTableValidationService::class)->assertValid(TableDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_name(): void
    {
        $data = $this->sampleTableDefinition('finance.core', 'invoice_list');
        $data['name'] = '';

        $this->expectException(TableValidationException::class);
        app(DynamicTableValidationService::class)->assertValid(TableDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_table_key(): void
    {
        $data = $this->sampleTableDefinition('finance.core', '');
        $data['table_key'] = '';

        $this->expectException(TableValidationException::class);
        app(DynamicTableValidationService::class)->assertValid(TableDefinition::fromArray($data));
    }

    public function test_registry_registers_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicTableRegistryService::class)->register(
            TableDefinition::fromArray($this->sampleTableDefinition('registry.dto.'.uniqid(), 'record_list')),
        );

        $this->assertNotEmpty($definition->publicId);
        $this->assertTrue(TableDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists());
    }

    public function test_registry_registers_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicTableRegistryService::class)->register(
            $this->sampleTableDefinition('registry.array.'.uniqid(), 'record_list'),
        );

        $this->assertSame('record_list', $definition->tableKey);
    }

    public function test_registry_duplicate_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $payload = $this->sampleTableDefinition('registry.dup.'.uniqid(), 'record_list');

        app(DynamicTableRegistryService::class)->register($payload);

        $this->expectException(TableRegistryException::class);
        app(DynamicTableRegistryService::class)->register($payload);
    }

    public function test_registry_list_and_find(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.list.'.uniqid();

        app(DynamicTableRegistryService::class)->register(
            $this->sampleTableDefinition($moduleKey, 'order_list'),
        );

        $found = app(DynamicTableRegistryService::class)->find($moduleKey, 'order_list');
        $listed = app(DynamicTableRegistryService::class)->list($moduleKey);

        $this->assertNotNull($found);
        $this->assertCount(1, $listed);
    }

    public function test_registry_register_from_manifest_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'manifest.module.'.uniqid();

        $registered = app(DynamicTableRegistryService::class)->registerFromManifestTables([
            ['key' => 'customer_list', 'name' => 'Customer List'],
            ['key' => 'supplier_list', 'name' => 'Supplier List'],
        ], $moduleKey);

        $this->assertCount(2, $registered);
        $this->assertSame('customer_list', $registered[0]->tableKey);
    }

    public function test_registry_find_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicTableRegistryService::class)->register(
            $this->sampleTableDefinition('registry.public.'.uniqid(), 'record_list'),
        );

        $found = app(DynamicTableRegistryService::class)->findByPublicId((string) $definition->publicId);

        $this->assertNotNull($found);
        $this->assertSame($definition->tableKey, $found->tableKey);
    }

    public function test_registry_find_by_entity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'entity.tables.'.uniqid();

        $payload = $this->sampleTableDefinition($moduleKey, 'product_list');
        $payload['entity_key'] = 'product';
        app(DynamicTableRegistryService::class)->register($payload);

        $found = app(DynamicTableRegistryService::class)->findByEntity($moduleKey, 'product');

        $this->assertCount(1, $found);
        $this->assertSame('product_list', $found[0]->tableKey);
    }

    public function test_mapper_to_reference_public_id_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicTableRegistryService::class)->register(
            $this->sampleTableDefinition('mapper.ref.'.uniqid(), 'product_list'),
        );

        $model = TableDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->firstOrFail();

        $reference = DynamicTableMapper::toReference($model);

        $this->assertArrayHasKey('public_id', $reference);
        $this->assertArrayNotHasKey('id', $reference);
    }

    public function test_generator_creates_list_table_from_entity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.entity.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'customer'),
        );

        $table = app(DynamicTableGeneratorService::class)->generateListTable($moduleKey, 'customer');

        $this->assertSame('customer_list', $table->tableKey);
        $this->assertSame('customer', $table->entityKey);
        $this->assertNotEmpty($table->columns);
    }

    public function test_generator_maps_string_field_to_text_column(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.string.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition($context, [
            'module_key' => $moduleKey,
            'entity_key' => 'item',
            'name' => 'Item',
            'status' => 'registered',
            'visibility' => 'organization',
            'ownership_scope' => 'organization',
            'fields' => [[
                'key' => 'title',
                'label' => 'Title',
                'type' => 'string',
                'required' => true,
                'searchable' => true,
            ]],
        ]);

        $table = app(DynamicTableGeneratorService::class)->generateListTable($moduleKey, 'item');

        $this->assertSame('text', $table->columns[0]->type);
        $this->assertTrue($table->columns[0]->searchable);
    }

    public function test_generator_maps_boolean_field_to_boolean_column(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.boolean.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition($context, [
            'module_key' => $moduleKey,
            'entity_key' => 'flag',
            'name' => 'Flag',
            'status' => 'registered',
            'visibility' => 'organization',
            'ownership_scope' => 'organization',
            'fields' => [[
                'key' => 'active',
                'label' => 'Active',
                'type' => 'boolean',
            ]],
        ]);

        $table = app(DynamicTableGeneratorService::class)->generateListTable($moduleKey, 'flag');

        $this->assertSame('boolean', $table->columns[0]->type);
    }

    public function test_renderer_returns_grid_structure(): void
    {
        $definition = TableDefinition::fromArray($this->sampleTableDefinition('render.'.uniqid(), 'record_list'));
        $rendered = app(DynamicTableRendererService::class)->render($definition, ['mode' => 'list']);

        $this->assertSame($definition->moduleKey, $rendered['metadata']['module_key']);
        $this->assertSame($definition->tableKey, $rendered['metadata']['table_key']);
        $this->assertArrayHasKey('columns', $rendered);
        $this->assertArrayHasKey('pagination', $rendered);
        $this->assertArrayNotHasKey('html', $rendered);
    }

    public function test_query_request_validation_accepts_valid_request(): void
    {
        $definition = TableDefinition::fromArray($this->sampleTableDefinition('query.ok.'.uniqid(), 'record_list'));

        app(DynamicTableValidationService::class)->assertValidQuery(
            new TableQueryRequest(
                moduleKey: $definition->moduleKey,
                tableKey: $definition->tableKey,
            ),
            $definition,
        );

        $this->assertTrue(true);
    }

    public function test_query_request_validation_rejects_unknown_filter_column(): void
    {
        $definition = TableDefinition::fromArray($this->sampleTableDefinition('query.fail.'.uniqid(), 'record_list'));

        $this->expectException(TableValidationException::class);
        app(DynamicTableValidationService::class)->assertValidQuery(
            new TableQueryRequest(
                moduleKey: $definition->moduleKey,
                tableKey: $definition->tableKey,
                filters: [new TableFilter(columnKey: 'unknown', operator: 'equals', value: 'x')],
            ),
            $definition,
        );
    }

    public function test_query_returns_empty_paginated_result(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleTableDefinition('query.empty.'.uniqid(), 'record_list'),
        );

        $result = $service->queryTable($context, new TableQueryRequest(
            moduleKey: $definition->moduleKey,
            tableKey: $definition->tableKey,
        ));

        $this->assertSame(0, $result->total);
        $this->assertSame([], $result->rows);
        $this->assertSame(1, $result->page);
    }

    public function test_filter_evaluator_equals(): void
    {
        $evaluator = app(DynamicTableFilterEvaluator::class);
        $filter = new TableFilter(columnKey: 'name', operator: TableFilterOperator::Equals->value, value: 'Acme');

        $this->assertTrue($evaluator->evaluate($filter, 'Acme'));
        $this->assertFalse($evaluator->evaluate($filter, 'Other'));
    }

    public function test_filter_evaluator_contains(): void
    {
        $evaluator = app(DynamicTableFilterEvaluator::class);
        $filter = new TableFilter(columnKey: 'name', operator: TableFilterOperator::Contains->value, value: 'ac');

        $this->assertTrue($evaluator->evaluate($filter, 'Acme Corp'));
    }

    public function test_filter_evaluator_greater_than(): void
    {
        $evaluator = app(DynamicTableFilterEvaluator::class);
        $filter = new TableFilter(columnKey: 'amount', operator: TableFilterOperator::GreaterThan->value, value: 10);

        $this->assertTrue($evaluator->evaluate($filter, 15));
        $this->assertFalse($evaluator->evaluate($filter, 5));
    }

    public function test_filter_evaluator_is_empty(): void
    {
        $evaluator = app(DynamicTableFilterEvaluator::class);
        $filter = new TableFilter(columnKey: 'name', operator: TableFilterOperator::IsEmpty->value);

        $this->assertTrue($evaluator->evaluate($filter, null));
        $this->assertFalse($evaluator->evaluate($filter, 'value'));
    }

    public function test_filter_evaluator_is_not_empty(): void
    {
        $evaluator = app(DynamicTableFilterEvaluator::class);
        $filter = new TableFilter(columnKey: 'name', operator: TableFilterOperator::IsNotEmpty->value);

        $this->assertTrue($evaluator->evaluate($filter, 'value'));
        $this->assertFalse($evaluator->evaluate($filter, ''));
    }

    public function test_sort_resolver_uses_default_sort(): void
    {
        $definition = TableDefinition::fromArray(array_merge(
            $this->sampleTableDefinition('sort.default.'.uniqid(), 'record_list'),
            ['default_sort' => ['column_key' => 'name', 'direction' => 'asc']],
        ));

        $sorts = app(DynamicTableSortService::class)->resolve($definition);

        $this->assertCount(1, $sorts);
        $this->assertSame('name', $sorts[0]->columnKey);
    }

    public function test_sort_resolver_applies_requested_sort(): void
    {
        $definition = TableDefinition::fromArray($this->sampleTableDefinition('sort.request.'.uniqid(), 'record_list'));
        $sorts = app(DynamicTableSortService::class)->resolve($definition, [
            new \App\Modules\Sdk\Table\Data\TableSort(columnKey: 'name', direction: 'desc'),
        ]);

        $this->assertSame('desc', $sorts[0]->direction);
    }

    public function test_view_save_load_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleTableDefinition('views.'.uniqid(), 'record_list'),
        );

        $saved = $service->saveView($context, TableView::fromArray([
            'module_key' => $definition->moduleKey,
            'table_key' => $definition->tableKey,
            'name' => 'My View',
            'columns' => [['key' => 'name', 'label' => 'Name', 'type' => 'text']],
        ]));

        $views = $service->listViews($context, $definition->moduleKey, $definition->tableKey);

        $this->assertCount(1, $views);
        $this->assertSame('My View', $views[0]->name);
        $this->assertTrue(TableViewModel::query()->where('public_id', $saved->publicId)->exists());

        $service->deleteViewByPublicId($context, (string) $saved->publicId);
        $this->assertSame(0, TableViewModel::query()->where('public_id', $saved->publicId)->count());
    }

    public function test_activity_logged_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $activity = app(\App\Services\Table\DynamicTableActivityService::class)->log(
            scope: new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $definition->moduleKey,
            ),
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            tableDefinitionId: TableDefinitionModel::query()->where('public_id', $definition->publicId)->value('id'),
            action: 'table.viewed',
        );

        $this->assertArrayHasKey('public_id', $activity);
    }

    public function test_health_service_reports_status(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicTableHealthService::class)->health($context);

        $this->assertTrue($health->enabled);
        $this->assertContains('No table definitions are registered yet.', $health->warnings);
    }

    public function test_statistics_service_counts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleTableDefinition('stats.'.uniqid(), 'record_list'),
        );

        $service->saveView($context, TableView::fromArray([
            'module_key' => $definition->moduleKey,
            'table_key' => $definition->tableKey,
            'name' => 'Stats View',
        ]));

        $stats = app(DynamicTableStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertGreaterThanOrEqual(1, $stats->definitions);
        $this->assertGreaterThanOrEqual(1, $stats->views);
    }

    public function test_development_service_list_definitions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $service->registerDefinition($context, $this->sampleTableDefinition('dev.list.'.uniqid(), 'record_list'));

        $definitions = $service->listDefinitions($context);

        $this->assertNotEmpty($definitions);
    }

    public function test_development_service_show_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $registered = $service->registerDefinition($context, $this->sampleTableDefinition('dev.show.'.uniqid(), 'record_list'));

        $definition = $service->showDefinition($context, $registered->moduleKey, $registered->tableKey);

        $this->assertSame($registered->tableKey, $definition->tableKey);
    }

    public function test_development_service_render_table(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicTableDevelopmentService::class);
        $registered = $service->registerDefinition($context, $this->sampleTableDefinition('dev.render.'.uniqid(), 'record_list'));

        $rendered = $service->renderTable($context, $registered, ['mode' => 'list']);

        $this->assertSame($registered->tableKey, $rendered['metadata']['table_key']);
    }

    public function test_development_service_health(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicTableDevelopmentService::class)->health($context);

        $this->assertInstanceOf(TableHealthReport::class, $health);
    }

    public function test_development_service_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $statistics = app(DynamicTableDevelopmentService::class)->statistics($context);

        $this->assertInstanceOf(TableStatistics::class, $statistics);
    }

    public function test_api_index_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleTable($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/tables');

        $response->assertOk();
    }

    public function test_api_show_table(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey);

        $response->assertOk();
        $this->assertSame($definition->tableKey, $response->json('data.table_key') ?? $response->json('table_key'));
    }

    public function test_api_render_table(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/render');

        $response->assertOk();
        $this->assertArrayHasKey('columns', $response->json('data') ?? $response->json());
    }

    public function test_api_query_table(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/query', [
                'page' => 1,
                'per_page' => 10,
            ]);

        $response->assertOk();
        $this->assertSame(0, $response->json('data.total') ?? $response->json('total'));
    }

    public function test_api_view_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/views', [
                'name' => 'API View',
            ]);
        $createResponse->assertCreated();

        $listResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/views');
        $listResponse->assertOk();
        $this->assertNotEmpty($listResponse->json('data') ?? $listResponse->json());
    }

    public function test_api_delete_view_static_route(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        $view = app(DynamicTableDevelopmentService::class)->saveView($context, TableView::fromArray([
            'module_key' => $definition->moduleKey,
            'table_key' => $definition->tableKey,
            'name' => 'Delete Me',
        ]));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/tables/views/'.$view->publicId);

        $response->assertNoContent();
    }

    public function test_permission_catalog_includes_table_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'tables.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'tables.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'tables.query')->exists());
        $this->assertTrue(Permission::query()->where('key', 'tables.export')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_member_can_query_and_export(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleTable($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/query', [
                'page' => 1,
            ]);

        $response->assertOk();
    }

    public function test_viewer_cannot_query(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleTable($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/query', [
                'page' => 1,
            ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_save_view(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleTable($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/tables/'.$definition->moduleKey.'/'.$definition->tableKey.'/views', [
                'name' => 'Viewer View',
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_isolation_for_views(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        $definition = $this->registerSampleTable($contextA);

        $view = app(DynamicTableDevelopmentService::class)->saveView($contextA, TableView::fromArray([
            'module_key' => $definition->moduleKey,
            'table_key' => $definition->tableKey,
            'name' => 'Tenant A View',
        ]));

        app()->instance(TenantContext::class, $contextB);

        $this->expectException(\App\Modules\Sdk\Table\Exceptions\TableNotFoundException::class);
        app(DynamicTableDevelopmentService::class)->deleteViewByPublicId($contextB, (string) $view->publicId);
    }

    public function test_module_doctor_includes_tables_health(): void
    {
        $this->seedHeosPlatform();

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('tables', $report->platformSummary['enterprise']);
    }

    public function test_workspace_runtime_includes_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['tables'] ?? false);
        $this->assertArrayHasKey('tables', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_config_tables_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.tables.enabled', true));
    }

    public function test_audit_action_recorded_on_render(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleTable($context);

        app(DynamicTableDevelopmentService::class)->renderTable($context, $definition);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TableRendered->value)->exists());
    }

    public function test_business_module_base_tables_integration(): void
    {
        $module = new class extends BusinessModuleBase
        {
            protected string $moduleKey = 'demo.tables';

            public function tables(): array
            {
                return [[
                    'table_key' => 'customer_list',
                    'name' => 'Customer List',
                ]];
            }
        };

        $this->assertCount(1, $module->tables());
        $this->assertSame('customer_list', $module->tables()[0]['table_key']);
    }

    public function test_business_module_installer_registers_manifest_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'table.install.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray([
                'module_key' => $moduleKey,
                'name' => 'Table Install Module',
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
                'tables' => [[
                    'table_key' => 'order_list',
                    'name' => 'Order List',
                    'columns' => [['key' => 'name', 'label' => 'Name', 'type' => 'text']],
                ]],
                'dependencies' => ['heos.core'],
            ]),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(TableDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('table_key', 'order_list')
            ->exists());
    }

    public function test_missing_table_guard(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->expectException(\App\Modules\Sdk\Table\Exceptions\TableNotFoundException::class);
        app(DynamicTableDevelopmentService::class)->showDefinition($context, 'missing.module', 'missing_table');
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleTableDefinition(string $moduleKey, string $tableKey): array
    {
        return [
            'module_key' => $moduleKey,
            'table_key' => $tableKey,
            'name' => $tableKey === '' ? '' : ucwords(str_replace(['.', '-', '_'], ' ', $tableKey)),
            'description' => 'Sample table definition.',
            'type' => 'list',
            'status' => 'registered',
            'visibility' => 'organization',
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'sortable' => true,
                'filterable' => true,
                'searchable' => true,
            ]],
            'pagination' => ['page' => 1, 'per_page' => 25],
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
        $result = $this->provisionTestOrganization($user, ['slug' => 'dynamic-tables-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function registerSampleTable(TenantContext $context): TableDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(DynamicTableDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleTableDefinition('sample.tables.'.uniqid(), 'record_list'),
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
