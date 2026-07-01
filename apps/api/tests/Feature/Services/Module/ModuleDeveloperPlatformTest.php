<?php

namespace Tests\Feature\Services\Module;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Modules\Demo\DemoModule;
use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleHealthContext;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleHealthReport;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleNavigationItem;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Data\ModuleRouteDefinition;
use App\Modules\Sdk\Data\ModuleSettingDefinition;
use App\Modules\Sdk\Data\ModuleValidationIssue;
use App\Modules\Sdk\Runtime\RuntimeContribution;
use App\Services\Module\ModuleDependencyGraphService;
use App\Services\Module\ModuleDocumentationService;
use App\Services\Module\ModuleHealthAggregator;
use App\Services\Module\ModuleInspectionService;
use App\Services\Module\ModuleValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ModuleDeveloperPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_validation_service_passes_for_platform_modules(): void
    {
        $report = app(ModuleValidationService::class)->validate();

        $this->assertTrue($report->isValid());
    }

    public function test_validation_service_detects_duplicate_permission_keys(): void
    {
        $report = app(ModuleValidationService::class)->validateModule(new InvalidPermissionModule);

        $this->assertFalse($report->isValid());
        $this->assertTrue(collect($report->issues)->contains(
            fn (ModuleValidationIssue $issue) => $issue->code === 'duplicate_permission_key',
        ));
    }

    public function test_validation_service_detects_duplicate_navigation_ids(): void
    {
        $report = app(ModuleValidationService::class)->validateModule(new InvalidNavigationModule);

        $this->assertTrue(collect($report->issues)->contains(
            fn (ModuleValidationIssue $issue) => $issue->code === 'duplicate_navigation_id',
        ));
    }

    public function test_validation_service_detects_duplicate_route_names(): void
    {
        $report = app(ModuleValidationService::class)->validateModule(new InvalidRouteModule);

        $this->assertTrue(collect($report->issues)->contains(
            fn (ModuleValidationIssue $issue) => $issue->code === 'duplicate_route_name',
        ));
    }

    public function test_validation_service_records_audit_event(): void
    {
        app(ModuleValidationService::class)->validate();

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::ModuleValidationExecuted->value)
                ->exists(),
        );
    }

    public function test_validation_audit_is_best_effort(): void
    {
        $recorder = Mockery::mock(\App\Services\Audit\AuditEventRecorder::class);
        $recorder->shouldReceive('record')->andThrow(new \RuntimeException('audit down'));

        $service = new ModuleValidationService(
            app(\App\Modules\Sdk\ModuleRegistry::class),
            app(\App\Modules\Sdk\ModuleManifestValidator::class),
            new \App\Services\Module\ModuleDeveloperAuditRecorder($recorder),
        );

        $service->validate();

        $this->assertTrue(true);
    }

    public function test_dependency_graph_returns_topological_order(): void
    {
        $graph = app(ModuleDependencyGraphService::class)->build();

        $coreIndex = array_search('core', $graph->topologicalOrder, true);
        $demoIndex = array_search('demo', $graph->topologicalOrder, true);

        $this->assertNotFalse($coreIndex);
        $this->assertNotFalse($demoIndex);
        $this->assertLessThan($demoIndex, $coreIndex);
    }

    public function test_dependency_graph_reverse_order_is_opposite(): void
    {
        $graph = app(ModuleDependencyGraphService::class)->build();

        $this->assertSame(array_reverse($graph->topologicalOrder), $graph->reverseOrder);
    }

    public function test_dependency_graph_exports_nodes_and_edges(): void
    {
        $graph = app(ModuleDependencyGraphService::class)->build()->exportGraph();

        $this->assertContains('demo', $graph['nodes']);
        $this->assertNotEmpty($graph['edges']);
    }

    public function test_dependency_graph_builds_dependency_tree(): void
    {
        $graph = app(ModuleDependencyGraphService::class)->build();

        $this->assertEqualsCanonicalizing(['core', 'workspace'], $graph->dependencyTree['demo']);
    }

    public function test_health_aggregator_reports_healthy_platform_modules(): void
    {
        $report = app(ModuleHealthAggregator::class)->aggregate();

        $this->assertSame('warning', $report->overallStatus);
        $this->assertSame('healthy', $report->moduleHealth['demo']['status']);
        $this->assertSame(2, $report->runtimeHealth['contributors']);
    }

    public function test_health_aggregator_reports_sync_missing_before_catalog_sync(): void
    {
        $report = app(ModuleHealthAggregator::class)->aggregate();

        $this->assertSame(4, $report->syncHealth['missing']);
        $this->assertSame(0, $report->syncHealth['synced']);
    }

    public function test_inspection_service_inspects_single_module(): void
    {
        $result = app(ModuleInspectionService::class)->inspect('demo');

        $this->assertNotNull($result);
        $this->assertSame('demo', $result->moduleKey);
        $this->assertTrue($result->runtimeContributor);
    }

    public function test_inspection_service_returns_null_for_unknown_module(): void
    {
        $this->assertNull(app(ModuleInspectionService::class)->inspect('missing-module'));
    }

    public function test_inspection_summary_counts_modules(): void
    {
        $summary = app(ModuleInspectionService::class)->summary();

        $this->assertSame(4, $summary->moduleCount);
        $this->assertSame(4, $summary->healthyCount);
        $this->assertSame(2, $summary->runtimeContributorCount);
    }

    public function test_inspection_statistics_include_catalog_counts(): void
    {
        $statistics = app(ModuleInspectionService::class)->statistics();

        $this->assertSame(4, $statistics['module_count']);
        $this->assertGreaterThan(0, $statistics['setting_count']);
        $this->assertSame(0, $statistics['permission_count']);
    }

    public function test_documentation_service_generates_module_markdown_files(): void
    {
        $directory = storage_path('framework/testing/docs-'.uniqid());
        $result = app(ModuleDocumentationService::class)->generate($directory);

        $this->assertFileExists($directory.'/core.md');
        $this->assertFileExists($directory.'/demo.md');
        $this->assertFileExists($directory.'/index.md');
        $this->assertSame(4, $result->moduleCount);
    }

    public function test_documentation_markdown_contains_module_metadata(): void
    {
        $directory = storage_path('framework/testing/docs-content-'.uniqid());
        app(ModuleDocumentationService::class)->generate($directory);

        $contents = file_get_contents($directory.'/demo.md');

        $this->assertStringContainsString('Demo Application', $contents);
        $this->assertStringContainsString('demo.preview', $contents);
        $this->assertStringContainsString('feature.enabled', $contents);
    }

    public function test_documentation_records_audit_event(): void
    {
        $directory = storage_path('framework/testing/docs-audit-'.uniqid());
        app(ModuleDocumentationService::class)->generate($directory);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::ModuleDocumentationGenerated->value)
                ->exists(),
        );
    }

    public function test_inspect_all_returns_sorted_modules(): void
    {
        $modules = app(ModuleInspectionService::class)->inspectAll();
        $keys = array_map(fn ($module) => $module->moduleKey, $modules);

        $this->assertSame(['core', 'demo', 'hosteady-admin', 'workspace'], $keys);
    }
}

class InvalidPermissionModule extends AbstractApplicationModule
{
    public function key(): string
    {
        return 'invalid-permission';
    }

    public function name(): string
    {
        return 'Invalid Permission Module';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function permissions(): array
    {
        return [
            new ModulePermission('invalid-permission.view', 'View'),
            new ModulePermission('invalid-permission.view', 'View Duplicate'),
        ];
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
        );
    }
}

class InvalidNavigationModule extends AbstractApplicationModule
{
    public function key(): string
    {
        return 'invalid-navigation';
    }

    public function name(): string
    {
        return 'Invalid Navigation Module';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function navigation(): array
    {
        return [
            new ModuleNavigationItem('nav-1', $this->key(), 'One', null, 'route.one', null, 1),
            new ModuleNavigationItem('nav-1', $this->key(), 'Two', null, 'route.two', null, 2),
        ];
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: '01900000-0000-7000-8000-000000000098',
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
        );
    }
}

class InvalidRouteModule extends AbstractApplicationModule
{
    public function key(): string
    {
        return 'invalid-route';
    }

    public function name(): string
    {
        return 'Invalid Route Module';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function routes(): ModuleRouteCollection
    {
        return new ModuleRouteCollection([
            new ModuleRouteDefinition('GET', '/one', 'invalid-route.one'),
            new ModuleRouteDefinition('GET', '/two', 'invalid-route.one'),
        ]);
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: '01900000-0000-7000-8000-000000000097',
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
        );
    }
}
