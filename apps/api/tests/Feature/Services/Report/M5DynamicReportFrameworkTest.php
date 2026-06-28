<?php

namespace Tests\Feature\Services\Report;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Models\ReportSchedule as ReportScheduleModel;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportResult;
use App\Modules\Sdk\Report\Data\ReportFilter;
use App\Modules\Sdk\Report\Data\ReportHealthReport;
use App\Modules\Sdk\Report\Data\ReportRunResult;
use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use App\Modules\Sdk\Report\Data\ReportStatistics;
use App\Modules\Sdk\Report\Enums\ReportExportFormat;
use App\Modules\Sdk\Report\Enums\ReportFilterOperator;
use App\Modules\Sdk\Report\Enums\ReportRunStatus;
use App\Modules\Sdk\Report\Enums\ReportScheduleStatus;
use App\Modules\Sdk\Report\Exceptions\ReportExportException;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Modules\Sdk\Report\Exceptions\ReportRegistryException;
use App\Modules\Sdk\Report\Exceptions\ReportValidationException;
use App\Services\Dashboard\DynamicDashboardRegistryService;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Services\Report\DynamicReportDataProviderService;
use App\Services\Report\DynamicReportDevelopmentService;
use App\Services\Report\DynamicReportExportService;
use App\Services\Report\DynamicReportFilterService;
use App\Services\Report\DynamicReportGeneratorService;
use App\Services\Report\DynamicReportHealthService;
use App\Services\Report\DynamicReportMapper;
use App\Services\Report\DynamicReportRegistryService;
use App\Services\Report\DynamicReportRendererService;
use App\Services\Report\DynamicReportRunService;
use App\Services\Report\DynamicReportScheduleService;
use App\Services\Report\DynamicReportStatisticsService;
use App\Services\Report\DynamicReportValidationService;
use App\Services\Table\DynamicTableGeneratorService;
use App\Services\Table\DynamicTableRegistryService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5DynamicReportFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_report_definition_dto_roundtrip(): void
    {
        $definition = ReportDefinition::fromArray($this->sampleReportDefinition('crm.core', 'lead_list_report'));

        $roundtrip = ReportDefinition::fromArray($definition->toArray());

        $this->assertSame('crm.core', $roundtrip->moduleKey);
        $this->assertSame('lead_list_report', $roundtrip->reportKey);
        $this->assertSame('Lead List Report', $roundtrip->name);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new ReportHealthReport(
            enabled: true,
            definitions: 2,
            templates: 1,
            runs: 3,
            exports: 2,
            schedules: 1,
            warnings: ['No report definitions are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
        $this->assertSame(3, $report->jsonSerialize()['runs']);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = ReportStatistics::fromArray([
            'definitions' => 2,
            'templates' => 1,
            'runs' => 4,
            'exports' => 2,
            'schedules' => 1,
            'registered_modules' => ['crm.core'],
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['definitions']);
        $this->assertSame(['crm.core'], $statistics->registeredModules);
    }

    public function test_run_result_dto_serializes(): void
    {
        $result = ReportRunResult::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000901',
            'status' => ReportRunStatus::Completed->value,
            'parameters' => ['status' => 'active'],
            'result' => ['rows' => []],
            'started_at' => '2026-06-28T10:00:00Z',
            'completed_at' => '2026-06-28T10:00:01Z',
            'duration_ms' => 1000,
        ]);

        $this->assertSame(ReportRunStatus::Completed->value, $result->toArray()['status']);
        $this->assertSame(1000, $result->jsonSerialize()['duration_ms']);
    }

    public function test_export_result_dto_serializes(): void
    {
        $result = ReportExportResult::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000902',
            'export_format' => ReportExportFormat::Csv->value,
            'status' => 'completed',
            'file_reference' => ['placeholder' => true, 'path' => 'reports/demo/report.csv'],
        ]);

        $this->assertSame(ReportExportFormat::Csv->value, $result->toArray()['export_format']);
        $this->assertTrue($result->jsonSerialize()['file_reference']['placeholder']);
    }

    public function test_schedule_definition_dto_serializes(): void
    {
        $schedule = ReportScheduleDefinition::fromArray([
            'module_key' => 'crm.core',
            'report_key' => 'lead_list_report',
            'name' => 'Weekly Lead Report',
            'cron_expression' => '0 8 * * 1',
            'timezone' => 'UTC',
            'export_formats' => ['csv', 'pdf'],
            'recipients' => ['ops@example.com'],
        ]);

        $payload = $schedule->toArray();

        $this->assertSame('Weekly Lead Report', $payload['name']);
        $this->assertSame(['csv', 'pdf'], $schedule->jsonSerialize()['export_formats']);
    }

    public function test_validator_accepts_valid_definition(): void
    {
        app(DynamicReportValidationService::class)->assertValid(
            ReportDefinition::fromArray($this->sampleReportDefinition('procurement.core', 'supplier_list_report')),
        );

        $this->assertTrue(true);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleReportDefinition('INVALID KEY', 'record_list_report');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(ReportValidationException::class);
        app(DynamicReportValidationService::class)->assertValid(ReportDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_name(): void
    {
        $data = $this->sampleReportDefinition('finance.core', 'invoice_list_report');
        $data['name'] = '';

        $this->expectException(ReportValidationException::class);
        app(DynamicReportValidationService::class)->assertValid(ReportDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_report_key(): void
    {
        $data = $this->sampleReportDefinition('finance.core', '');
        $data['report_key'] = '';

        $this->expectException(ReportValidationException::class);
        app(DynamicReportValidationService::class)->assertValid(ReportDefinition::fromArray($data));
    }

    public function test_registry_registers_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicReportRegistryService::class)->register(
            ReportDefinition::fromArray($this->sampleReportDefinition('registry.dto.'.uniqid(), 'record_list_report')),
        );

        $this->assertNotEmpty($definition->publicId);
        $this->assertTrue(ReportDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists());
    }

    public function test_registry_registers_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicReportRegistryService::class)->register(
            $this->sampleReportDefinition('registry.array.'.uniqid(), 'record_list_report'),
        );

        $this->assertSame('record_list_report', $definition->reportKey);
    }

    public function test_registry_duplicate_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $payload = $this->sampleReportDefinition('registry.dup.'.uniqid(), 'record_list_report');

        app(DynamicReportRegistryService::class)->register($payload);

        $this->expectException(ReportRegistryException::class);
        app(DynamicReportRegistryService::class)->register($payload);
    }

    public function test_registry_list_and_find(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.list.'.uniqid();

        app(DynamicReportRegistryService::class)->register(
            $this->sampleReportDefinition($moduleKey, 'order_list_report'),
        );

        $found = app(DynamicReportRegistryService::class)->find($moduleKey, 'order_list_report');
        $listed = app(DynamicReportRegistryService::class)->list($moduleKey);

        $this->assertNotNull($found);
        $this->assertCount(1, $listed);
    }

    public function test_registry_manifest_reports(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.manifest.'.uniqid();

        $registered = app(DynamicReportRegistryService::class)->registerFromManifestReports([
            [
                'report_key' => 'sales_list_report',
                'name' => 'Sales List Report',
            ],
        ], $moduleKey);

        $this->assertCount(1, $registered);
        $this->assertSame('sales_list_report', $registered[0]->reportKey);
    }

    public function test_registry_find_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicReportRegistryService::class)->register(
            $this->sampleReportDefinition('registry.public.'.uniqid(), 'record_list_report'),
        );

        $found = app(DynamicReportRegistryService::class)->findByPublicId((string) $definition->publicId);

        $this->assertNotNull($found);
        $this->assertSame($definition->reportKey, $found->reportKey);
    }

    public function test_registry_find_by_entity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.entity.'.uniqid();

        $payload = $this->sampleReportDefinition($moduleKey, 'lead_list_report');
        $payload['entity_key'] = 'lead';
        app(DynamicReportRegistryService::class)->register($payload);

        $found = app(DynamicReportRegistryService::class)->findByEntity($moduleKey, 'lead');

        $this->assertCount(1, $found);
        $this->assertSame('lead', $found[0]->entityKey);
    }

    public function test_mapper_to_reference_returns_public_id_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicReportRegistryService::class)->register(
            $this->sampleReportDefinition('mapper.ref.'.uniqid(), 'record_list_report'),
        );

        $model = ReportDefinitionModel::query()->where('public_id', $definition->publicId)->firstOrFail();
        $reference = DynamicReportMapper::toReference($model);

        $this->assertArrayHasKey('public_id', $reference);
        $this->assertArrayNotHasKey('id', $reference);
    }

    public function test_generator_creates_entity_list_report(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.list.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'customer'),
        );

        $report = app(DynamicReportGeneratorService::class)->generateEntityReport($moduleKey, 'customer', 'list');

        $this->assertSame('customer_list_report', $report->reportKey);
        $this->assertNotEmpty($report->columns);
        $this->assertTrue($report->metadata['generated_from_entity'] ?? false);
    }

    public function test_generator_creates_entity_summary_report(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.summary.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'order'),
        );

        $report = app(DynamicReportGeneratorService::class)->generateEntityReport($moduleKey, 'order', 'summary');

        $this->assertSame('order_summary_report', $report->reportKey);
        $this->assertNotEmpty($report->aggregates);
        $this->assertNotEmpty($report->metrics);
        $this->assertSame('entity_placeholder', $report->metrics[0]->dataSourceType);
        $this->assertSame('summary', $report->metadata['report_variant'] ?? null);
    }

    public function test_generator_from_table(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.table.'.uniqid();

        app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition($moduleKey, 'invoice'),
        );

        $table = app(DynamicTableGeneratorService::class)->generateListTable($moduleKey, 'invoice');
        app(DynamicTableRegistryService::class)->register($table->toArray());

        $report = app(DynamicReportGeneratorService::class)->generateFromTable($table);

        $this->assertSame('invoice_list_report', $report->reportKey);
        $this->assertTrue($report->metadata['generated_from_table'] ?? false);
    }

    public function test_generator_from_dashboard(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'generator.dashboard.'.uniqid();

        $dashboard = app(DynamicDashboardRegistryService::class)->register([
            'module_key' => $moduleKey,
            'dashboard_key' => 'sales_dashboard',
            'name' => 'Sales Dashboard',
            'widgets' => [[
                'widget_key' => 'total_records',
                'name' => 'Total Records',
                'widget_type' => 'kpi_card',
                'data_source_type' => 'entity_count',
            ]],
        ]);

        $report = app(DynamicReportGeneratorService::class)->generateFromDashboard($dashboard);

        $this->assertSame('sales_dashboard_report', $report->reportKey);
        $this->assertTrue($report->metadata['generated_from_dashboard'] ?? false);
    }

    public function test_renderer_returns_structure_not_html(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $payload = app(DynamicReportRendererService::class)->render($definition);

        $this->assertArrayHasKey('metadata', $payload);
        $this->assertArrayHasKey('columns', $payload);
        $this->assertArrayHasKey('dataset', $payload);
        $this->assertArrayHasKey('aggregates', $payload);
        $this->assertArrayNotHasKey('html', $payload);
    }

    public function test_renderer_resolves_aggregates(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $payload = app(DynamicReportRendererService::class)->render($definition);

        $this->assertArrayHasKey('total_count', $payload['aggregates']);
        $this->assertSame(0, $payload['aggregates']['total_count']);
    }

    public function test_renderer_warnings_for_unsupported_aggregate(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicReportRegistryService::class)->register(array_merge(
            $this->sampleReportDefinition('renderer.warn.'.uniqid(), 'warn_report'),
            [
                'aggregates' => [[
                    'key' => 'unsupported_metric',
                    'function' => 'unsupported_fn',
                ]],
            ],
        ));

        $payload = app(DynamicReportRendererService::class)->render($definition);

        $this->assertNotEmpty($payload['aggregate_warnings']);
    }

    public function test_data_provider_static_source(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.static', 'static_report'),
            ['metadata' => ['data_source_type' => 'static']],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $types = DynamicReportDataProviderService::DATA_SOURCE_TYPES;

        $this->assertCount(8, $types);
        $this->assertContains('static', $types);
        $this->assertContains('custom_placeholder', $types);
        $this->assertSame('static', $dataset->metadata['source']);
        $this->assertTrue($dataset->metadata['placeholder'] ?? false);
    }

    public function test_data_provider_entity_placeholder(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.entity', 'entity_report'),
            ['entity_key' => 'lead'],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('entity_placeholder', $dataset->metadata['source']);
        $this->assertTrue($dataset->metadata['placeholder'] ?? false);
    }

    public function test_data_provider_table_placeholder(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.table', 'table_report'),
            ['table_key' => 'lead_list'],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('table_placeholder', $dataset->metadata['source']);
        $this->assertSame('lead_list', $dataset->metadata['table_key']);
    }

    public function test_data_provider_dashboard_placeholder(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.dashboard', 'dashboard_report'),
            ['dashboard_key' => 'sales_dashboard'],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('dashboard_placeholder', $dataset->metadata['source']);
        $this->assertSame('sales_dashboard', $dataset->metadata['dashboard_key']);
    }

    public function test_data_provider_form_submission_count(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.form', 'form_report'),
            ['metadata' => ['data_source_type' => 'form_submission_count']],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('form_submission_count', $dataset->metadata['source']);
        $this->assertTrue($dataset->metadata['placeholder'] ?? false);
    }

    public function test_data_provider_workflow_instance_count(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.workflow', 'workflow_report'),
            ['metadata' => ['data_source_type' => 'workflow_instance_count']],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('workflow_instance_count', $dataset->metadata['source']);
    }

    public function test_data_provider_approval_task_count(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.approval', 'approval_report'),
            ['metadata' => ['data_source_type' => 'approval_task_count']],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('approval_task_count', $dataset->metadata['source']);
    }

    public function test_data_provider_custom_placeholder(): void
    {
        $definition = ReportDefinition::fromArray(array_merge(
            $this->sampleReportDefinition('provider.custom', 'custom_report'),
            ['metadata' => ['data_source_type' => 'custom_placeholder']],
        ));

        $dataset = app(DynamicReportDataProviderService::class)->resolve($definition);

        $this->assertSame('custom_placeholder', $dataset->metadata['source']);
        $this->assertTrue($dataset->metadata['placeholder'] ?? false);
    }

    public function test_filter_evaluator_equals(): void
    {
        $filter = new ReportFilter(fieldKey: 'status', operator: ReportFilterOperator::Equals->value, value: 'active');
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'active'));
        $this->assertFalse($evaluator->evaluate($filter, 'inactive'));
    }

    public function test_filter_evaluator_contains(): void
    {
        $filter = new ReportFilter(fieldKey: 'name', operator: ReportFilterOperator::Contains->value, value: 'acme');
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'Acme Corp'));
    }

    public function test_filter_evaluator_greater_than(): void
    {
        $filter = new ReportFilter(fieldKey: 'amount', operator: ReportFilterOperator::GreaterThan->value, value: 10);
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 15));
        $this->assertFalse($evaluator->evaluate($filter, 5));
    }

    public function test_filter_evaluator_between(): void
    {
        $filter = new ReportFilter(fieldKey: 'score', operator: ReportFilterOperator::Between->value, value: [10, 20]);
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 15));
        $this->assertFalse($evaluator->evaluate($filter, 25));
    }

    public function test_filter_evaluator_is_empty(): void
    {
        $filter = new ReportFilter(fieldKey: 'notes', operator: ReportFilterOperator::IsEmpty->value);
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, null));
        $this->assertTrue($evaluator->evaluate($filter, ''));
    }

    public function test_filter_evaluator_is_not_empty(): void
    {
        $filter = new ReportFilter(fieldKey: 'notes', operator: ReportFilterOperator::IsNotEmpty->value);
        $evaluator = app(DynamicReportFilterService::class);

        $this->assertTrue($evaluator->evaluate($filter, 'hello'));
    }

    public function test_run_service_start_completes(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $run = app(DynamicReportRunService::class)->start(
            $definition->moduleKey,
            $definition->reportKey,
            ['status' => 'active'],
            $context->organization->id,
            $context->workspace->id,
        );

        $this->assertSame(ReportRunStatus::Completed->value, $run->status);
        $this->assertNotEmpty($run->publicId);

        $found = app(DynamicReportRunService::class)->findByPublicId($run->publicId);

        $this->assertNotNull($found);
        $this->assertSame($run->publicId, $found->publicId);
    }

    public function test_run_service_list_for_report(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportRunService::class)->start(
            $definition->moduleKey,
            $definition->reportKey,
            [],
            $context->organization->id,
            $context->workspace->id,
        );

        $runs = app(DynamicReportRunService::class)->listForReport($definition->moduleKey, $definition->reportKey);

        $this->assertCount(1, $runs);
    }

    public function test_export_service_accepts_valid_formats(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        foreach ([
            ReportExportFormat::Pdf->value,
            ReportExportFormat::Excel->value,
            ReportExportFormat::Csv->value,
            ReportExportFormat::Json->value,
        ] as $format) {
            $export = app(DynamicReportExportService::class)->requestExport(
                $definition->moduleKey,
                $definition->reportKey,
                $format,
            );

            $this->assertSame($format, $export->exportFormat);
            $this->assertSame('completed', $export->status);
            $this->assertTrue($export->fileReference['placeholder'] ?? false);
        }
    }

    public function test_export_service_invalid_format_throws(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $this->expectException(ReportExportException::class);
        app(DynamicReportExportService::class)->requestExport(
            $definition->moduleKey,
            $definition->reportKey,
            'xml',
        );
    }

    public function test_schedule_service_create(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $schedule = app(DynamicReportScheduleService::class)->create(new ReportScheduleDefinition(
            moduleKey: $definition->moduleKey,
            reportKey: $definition->reportKey,
            name: 'Daily Report',
            cronExpression: '0 8 * * *',
            exportFormats: ['csv'],
        ));

        $this->assertNotEmpty($schedule->publicId);
        $this->assertSame('Daily Report', $schedule->name);

        $schedules = app(DynamicReportScheduleService::class)->list($definition->moduleKey, $definition->reportKey);

        $this->assertCount(1, $schedules);
    }

    public function test_schedule_service_pause(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $schedule = app(DynamicReportScheduleService::class)->create(new ReportScheduleDefinition(
            moduleKey: $definition->moduleKey,
            reportKey: $definition->reportKey,
            name: 'Pausable Report',
            cronExpression: '0 9 * * *',
        ));

        $paused = app(DynamicReportScheduleService::class)->pause((string) $schedule->publicId);

        $this->assertSame(ReportScheduleStatus::Paused->value, $paused->status);
    }

    public function test_schedule_service_resume(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $schedule = app(DynamicReportScheduleService::class)->create(new ReportScheduleDefinition(
            moduleKey: $definition->moduleKey,
            reportKey: $definition->reportKey,
            name: 'Resumable Report',
            cronExpression: '0 10 * * *',
        ));

        app(DynamicReportScheduleService::class)->pause((string) $schedule->publicId);
        $resumed = app(DynamicReportScheduleService::class)->resume((string) $schedule->publicId);

        $this->assertSame(ReportScheduleStatus::Active->value, $resumed->status);
    }

    public function test_schedule_service_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $schedule = app(DynamicReportScheduleService::class)->create(new ReportScheduleDefinition(
            moduleKey: $definition->moduleKey,
            reportKey: $definition->reportKey,
            name: 'Deletable Report',
            cronExpression: '0 11 * * *',
        ));

        app(DynamicReportScheduleService::class)->delete((string) $schedule->publicId);

        $this->assertFalse(ReportScheduleModel::query()->where('public_id', $schedule->publicId)->exists());
    }

    public function test_activity_logging_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $entries = app(DynamicReportDevelopmentService::class)->listActivity(
            $context,
            (string) $definition->publicId,
        );

        $this->assertIsArray($entries);
    }

    public function test_health_service_warnings_when_empty(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $report = app(DynamicReportHealthService::class)->health($context);

        $this->assertTrue($report->enabled);
        $this->assertContains('No report definitions are registered yet.', $report->warnings);
    }

    public function test_statistics_counts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleReport($context);

        $stats = app(DynamicReportStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertGreaterThanOrEqual(1, $stats->definitions);
    }

    public function test_development_service_list_show_render(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $listed = app(DynamicReportDevelopmentService::class)->listDefinitions($context);
        $shown = app(DynamicReportDevelopmentService::class)->showDefinition(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
        );
        $rendered = app(DynamicReportDevelopmentService::class)->renderReport($context, $shown);

        $this->assertNotEmpty($listed);
        $this->assertSame($definition->reportKey, $shown->reportKey);
        $this->assertArrayHasKey('dataset', $rendered);
    }

    public function test_development_service_run_export_and_schedule(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $run = app(DynamicReportDevelopmentService::class)->runReport(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
        );
        $export = app(DynamicReportDevelopmentService::class)->exportReport(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
            ReportExportFormat::Pdf->value,
        );
        $schedule = app(DynamicReportDevelopmentService::class)->createSchedule(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
            ReportScheduleDefinition::fromArray([
                'module_key' => $definition->moduleKey,
                'report_key' => $definition->reportKey,
                'name' => 'Dev Schedule',
                'cron_expression' => '0 7 * * *',
            ]),
        );

        $this->assertSame(ReportRunStatus::Completed->value, $run->status);
        $this->assertSame(ReportExportFormat::Pdf->value, $export->exportFormat);
        $this->assertSame('Dev Schedule', $schedule->name);
    }

    public function test_development_service_health_and_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicReportDevelopmentService::class)->health($context);
        $statistics = app(DynamicReportDevelopmentService::class)->statistics($context);

        $this->assertInstanceOf(ReportHealthReport::class, $health);
        $this->assertInstanceOf(ReportStatistics::class, $statistics);
    }

    public function test_api_index_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleReport($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports');

        $response->assertOk();
    }

    public function test_api_show_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey);

        $response->assertOk()->assertJsonPath('data.report_key', $definition->reportKey);
    }

    public function test_api_render_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/render');

        $response->assertOk()->assertJsonStructure(['data' => ['metadata', 'columns', 'dataset']]);
    }

    public function test_api_run_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/run', [
                'parameters' => ['status' => 'active'],
            ]);

        $response->assertOk()->assertJsonPath('data.status', ReportRunStatus::Completed->value);
    }

    public function test_api_export_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/export', [
                'export_format' => 'csv',
            ]);

        $response->assertOk()->assertJsonPath('data.export_format', 'csv');
    }

    public function test_api_runs_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportRunService::class)->start(
            $definition->moduleKey,
            $definition->reportKey,
            [],
            $context->organization->id,
            $context->workspace->id,
        );

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/runs');

        $response->assertOk();
    }

    public function test_api_exports_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportExportService::class)->requestExport(
            $definition->moduleKey,
            $definition->reportKey,
            ReportExportFormat::Json->value,
        );

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/exports');

        $response->assertOk();
    }

    public function test_api_schedules_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        $create = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/schedules', [
                'name' => 'API Schedule',
                'cron_expression' => '0 6 * * *',
            ]);

        $create->assertCreated();

        $list = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/schedules');

        $list->assertOk();
    }

    public function test_permission_catalog_includes_report_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'reports.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'reports.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'reports.run')->exists());
        $this->assertTrue(Permission::query()->where('key', 'reports.export')->exists());
        $this->assertTrue(Permission::query()->where('key', 'reports.schedule')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_member_can_run(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleReport($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/run');

        $response->assertOk();
    }

    public function test_member_can_export(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleReport($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/export', [
                'export_format' => 'pdf',
            ]);

        $response->assertOk();
    }

    public function test_viewer_cannot_run(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleReport($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/reports/'.$definition->moduleKey.'/'.$definition->reportKey.'/run');

        $response->assertForbidden();
    }

    public function test_module_doctor_includes_reports_health(): void
    {
        $this->seedHeosPlatform();

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('reports', $report->platformSummary['enterprise']);
    }

    public function test_workspace_runtime_includes_reports(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['reports'] ?? false);
        $this->assertArrayHasKey('reports', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_config_reports_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.reports.enabled', true));
    }

    public function test_audit_action_recorded_on_render(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportDevelopmentService::class)->renderReport($context, $definition);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportRendered->value)->exists());
    }

    public function test_audit_action_recorded_on_run(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportDevelopmentService::class)->runReport(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportRunStarted->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportRunCompleted->value)->exists());
    }

    public function test_audit_action_recorded_on_export(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportDevelopmentService::class)->exportReport(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
            ReportExportFormat::Csv->value,
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportExportRequested->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportExportCompleted->value)->exists());
    }

    public function test_audit_action_recorded_on_schedule(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleReport($context);

        app(DynamicReportDevelopmentService::class)->createSchedule(
            $context,
            $definition->moduleKey,
            $definition->reportKey,
            ReportScheduleDefinition::fromArray([
                'module_key' => $definition->moduleKey,
                'report_key' => $definition->reportKey,
                'name' => 'Audit Schedule',
                'cron_expression' => '0 5 * * *',
            ]),
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReportScheduleCreated->value)->exists());
    }

    public function test_business_module_base_reports_integration(): void
    {
        $module = new class extends BusinessModuleBase
        {
            protected string $moduleKey = 'demo.reports';

            public function reports(): array
            {
                return [[
                    'report_key' => 'customer_list_report',
                    'name' => 'Customer List Report',
                ]];
            }
        };

        $this->assertCount(1, $module->reports());
        $this->assertSame('customer_list_report', $module->reports()[0]['report_key']);
    }

    public function test_business_module_installer_registers_manifest_reports(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'report.install.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray([
                'module_key' => $moduleKey,
                'name' => 'Report Install Module',
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
                'reports' => [[
                    'report_key' => 'order_list_report',
                    'name' => 'Order List Report',
                ]],
                'dependencies' => ['heos.core'],
            ]),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(ReportDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('report_key', 'order_list_report')
            ->exists());
    }

    public function test_missing_report_guard(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->expectException(ReportNotFoundException::class);
        app(DynamicReportDevelopmentService::class)->showDefinition($context, 'missing.module', 'missing_report');
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleReportDefinition(string $moduleKey, string $reportKey): array
    {
        return [
            'module_key' => $moduleKey,
            'report_key' => $reportKey,
            'name' => $reportKey === '' ? '' : ucwords(str_replace(['.', '-', '_'], ' ', $reportKey)),
            'description' => 'Sample report definition.',
            'type' => 'list',
            'status' => 'registered',
            'visibility' => 'organization',
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'text',
            ]],
            'aggregates' => [[
                'key' => 'total_count',
                'function' => 'count',
                'label' => 'Total Records',
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
        $result = $this->provisionTestOrganization($user, ['slug' => 'dynamic-reports-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function registerSampleReport(TenantContext $context): ReportDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(DynamicReportDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleReportDefinition('sample.reports.'.uniqid(), 'record_list_report'),
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
