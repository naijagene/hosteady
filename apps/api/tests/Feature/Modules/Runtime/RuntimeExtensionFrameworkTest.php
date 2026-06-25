<?php

namespace Tests\Feature\Modules\Runtime;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Contracts\RuntimeModuleContributor;
use App\Modules\Sdk\Runtime\RuntimeContribution;
use App\Modules\Sdk\Runtime\RuntimeContributionCollection;
use App\Modules\Sdk\Runtime\RuntimeContributorPipeline;
use App\Modules\Sdk\Runtime\RuntimeExtensionManager;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Module\RuntimeContributionAuditRecorder;
use App\Services\Runtime\RuntimeDiagnosticsService;
use App\Services\Runtime\RuntimeSnapshotSerializer;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceRuntimeVersionCalculator;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeExtensionFrameworkTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_runtime_contribution_is_readonly_dto(): void
    {
        $contribution = new RuntimeContribution(
            moduleKey: 'demo',
            priority: 5,
            capabilities: ['demo.runtime'],
            navigation: [['label' => 'Demo']],
            featureFlags: ['demo.preview' => true],
            runtimeMetadata: ['demo' => ['enabled' => true]],
            diagnostics: [['status' => 'healthy']],
            settingsMetadata: ['feature.enabled' => ['source' => 'module']],
            dependencies: ['core'],
            warnings: ['sample warning'],
        );

        $this->assertSame('demo', $contribution->moduleKey);
        $this->assertSame(5, $contribution->priority);
        $this->assertSame(['demo.runtime'], $contribution->capabilities);
    }

    public function test_runtime_contribution_empty_factory(): void
    {
        $contribution = RuntimeContribution::empty('core', 1);

        $this->assertSame('core', $contribution->moduleKey);
        $this->assertSame(1, $contribution->priority);
        $this->assertSame([], $contribution->capabilities);
    }

    public function test_collection_find_and_count(): void
    {
        $collection = (new RuntimeContributionCollection)
            ->add(RuntimeContribution::empty('core'))
            ->add(RuntimeContribution::empty('demo'));

        $this->assertSame(2, $collection->count());
        $this->assertNotNull($collection->find('demo'));
        $this->assertNull($collection->find('missing'));
    }

    public function test_collection_ordered_is_stable_by_module_key(): void
    {
        $collection = (new RuntimeContributionCollection)
            ->add(RuntimeContribution::empty('workspace'))
            ->add(RuntimeContribution::empty('core'))
            ->add(RuntimeContribution::empty('demo'));

        $keys = array_map(
            fn (RuntimeContribution $contribution) => $contribution->moduleKey,
            $collection->ordered()->all(),
        );

        $this->assertSame(['core', 'demo', 'workspace'], $keys);
    }

    public function test_merge_unions_capabilities(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', capabilities: ['x', 'y']))
            ->add(new RuntimeContribution(moduleKey: 'b', capabilities: ['y', 'z']))
            ->merge();

        $this->assertSame(['x', 'y', 'z'], $merged['capabilities']);
    }

    public function test_merge_appends_navigation(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', navigation: [['label' => 'A']]))
            ->add(new RuntimeContribution(moduleKey: 'b', navigation: [['label' => 'B']]))
            ->merge();

        $this->assertCount(2, $merged['navigation']);
        $this->assertSame('A', $merged['navigation'][0]['label']);
        $this->assertSame('B', $merged['navigation'][1]['label']);
    }

    public function test_merge_feature_flags_last_writer_wins(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', featureFlags: ['flag' => false]))
            ->add(new RuntimeContribution(moduleKey: 'b', featureFlags: ['flag' => true]))
            ->merge();

        $this->assertTrue($merged['feature_flags']['flag']);
    }

    public function test_merge_deep_merges_runtime_metadata(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', runtimeMetadata: ['demo' => ['a' => 1]]))
            ->add(new RuntimeContribution(moduleKey: 'b', runtimeMetadata: ['demo' => ['b' => 2]]))
            ->merge();

        $this->assertSame(['a' => 1, 'b' => 2], $merged['runtime_metadata']['demo']);
    }

    public function test_merge_appends_diagnostics(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', diagnostics: [['status' => 'a']]))
            ->add(new RuntimeContribution(moduleKey: 'b', diagnostics: [['status' => 'b']]))
            ->merge();

        $this->assertCount(2, $merged['diagnostics']);
    }

    public function test_merge_settings_metadata_by_key(): void
    {
        $merged = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'a', settingsMetadata: ['feature.enabled' => ['source' => 'a']]))
            ->add(new RuntimeContribution(moduleKey: 'b', settingsMetadata: ['feature.enabled' => ['source' => 'b']]))
            ->merge();

        $this->assertSame(['source' => 'b'], $merged['settings_metadata']['feature.enabled']);
    }

    public function test_pipeline_orders_by_dependency_then_priority_then_key(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $context = new TestModuleRuntimeContext;

        $report = $pipeline->execute($context, [
            new TestRuntimeContributor('z-module', priority: 1),
            new TestRuntimeContributor('a-module', priority: 99, dependencies: ['core']),
            new TestRuntimeContributor('core', priority: 99),
            new TestRuntimeContributor('b-module', priority: 0),
        ]);

        $keys = array_map(
            fn ($result) => $result->moduleKey,
            array_values(array_filter($report->results, fn ($result) => $result->success)),
        );

        $this->assertSame(['b-module', 'z-module', 'core', 'a-module'], $keys);
        $this->assertLessThan(array_search('a-module', $keys, true), array_search('core', $keys, true));
    }

    public function test_pipeline_warns_on_missing_dependency(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $report = $pipeline->execute(new TestModuleRuntimeContext, [
            new TestRuntimeContributor('demo', dependencies: ['missing-module']),
        ]);

        $this->assertTrue(collect($report->warnings)->contains(
            fn (string $warning) => str_contains($warning, 'missing-module'),
        ));
        $this->assertTrue($report->results[0]->success);
    }

    public function test_pipeline_skips_circular_dependencies(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $report = $pipeline->execute(new TestModuleRuntimeContext, [
            new TestRuntimeContributor('alpha', dependencies: ['beta']),
            new TestRuntimeContributor('beta', dependencies: ['alpha']),
        ]);

        $this->assertTrue(collect($report->warnings)->contains(
            fn (string $warning) => str_contains($warning, 'Circular dependency'),
        ));
        $this->assertSame(2, $report->skippedCount);
    }

    public function test_pipeline_isolates_contributor_failures(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $report = $pipeline->execute(new TestModuleRuntimeContext, [
            new TestRuntimeContributor('broken', shouldThrow: true),
            new TestRuntimeContributor('healthy'),
        ]);

        $this->assertFalse($report->results[0]->success);
        $this->assertTrue($report->results[1]->success);
        $this->assertSame(1, $report->executedCount);
    }

    public function test_pipeline_validates_module_key_mismatch(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $report = $pipeline->execute(new TestModuleRuntimeContext, [
            new MismatchedRuntimeContributor,
            new TestRuntimeContributor('healthy'),
        ]);

        $this->assertFalse($report->results[0]->success);
        $this->assertStringContainsString('does not match contributor', (string) $report->results[0]->error);
        $this->assertTrue($report->results[1]->success);
    }

    public function test_extension_manager_merges_platform_capabilities(): void
    {
        $manager = app(RuntimeExtensionManager::class);
        $collection = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'demo', capabilities: ['demo.runtime']));

        $merged = $manager->mergeCapabilities(
            ['audit' => true, 'settings' => true],
            $collection,
        );

        $this->assertTrue($merged['audit']);
        $this->assertTrue($merged['demo.runtime']);
    }

    public function test_fingerprint_changes_when_feature_flag_changes(): void
    {
        $calculator = app(WorkspaceRuntimeVersionCalculator::class);
        $manifest = $this->sampleManifest();

        $beforeFingerprint = (new RuntimeContributionCollection)->fingerprint();
        $afterFingerprint = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'demo', featureFlags: ['demo.preview' => true]))
            ->fingerprint();

        $this->assertNotSame(
            $calculator->calculate($manifest, $beforeFingerprint),
            $calculator->calculate($manifest, $afterFingerprint),
        );
    }

    public function test_fingerprint_changes_when_capability_changes(): void
    {
        $calculator = app(WorkspaceRuntimeVersionCalculator::class);
        $manifest = $this->sampleManifest();

        $beforeFingerprint = (new RuntimeContributionCollection)->fingerprint();
        $afterFingerprint = (new RuntimeContributionCollection)
            ->add(new RuntimeContribution(moduleKey: 'demo', capabilities: ['demo.runtime']))
            ->fingerprint();

        $this->assertNotSame(
            $calculator->calculate($manifest, $beforeFingerprint),
            $calculator->calculate($manifest, $afterFingerprint),
        );
    }

    public function test_pipeline_stable_sort_by_module_key_when_priority_equal(): void
    {
        $pipeline = app(RuntimeContributorPipeline::class);
        $report = $pipeline->execute(new TestModuleRuntimeContext, [
            new TestRuntimeContributor('charlie', priority: 0),
            new TestRuntimeContributor('alpha', priority: 0),
            new TestRuntimeContributor('bravo', priority: 0),
        ]);

        $keys = array_map(
            fn ($result) => $result->moduleKey,
            array_values(array_filter($report->results, fn ($result) => $result->success)),
        );

        $this->assertSame(['alpha', 'bravo', 'charlie'], $keys);
    }

    public function test_resolver_merges_demo_module_contributions(): void
    {
        $this->seedHeosPlatform();
        $context = $this->tenantContextWithDemo();

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['demo.runtime']);
        $this->assertNotEmpty($runtime->navigation);
        $this->assertTrue($runtime->featureFlags['demo.preview']);
        $this->assertNotEmpty($runtime->moduleDiagnostics);
        $this->assertArrayHasKey('feature.enabled', $runtime->settingsMetadata);
    }

    public function test_runtime_version_changes_when_demo_disabled(): void
    {
        $this->seedHeosPlatform();
        $context = $this->tenantContextWithDemo();
        $provider = app(WorkspaceRuntimeProvider::class);

        $before = $provider->resolveSummary($context)->runtimeVersion;

        $demo = \App\Models\WorkspaceApplication::query()
            ->whereHas('application', fn ($query) => $query->where('key', 'demo'))
            ->firstOrFail();

        app(\App\Services\WorkspaceApplication\WorkspaceApplicationService::class)
            ->disable($context, $demo->public_id);

        $after = $provider->resolveSummary($context)->runtimeVersion;

        $this->assertNotSame($before, $after);
    }

    public function test_diagnostics_exposes_module_contribution_summary(): void
    {
        $this->seedHeosPlatform();
        $context = $this->tenantContextWithDemo();

        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($context);

        $this->assertNotNull($diagnostics->moduleContributions);
        $this->assertArrayHasKey('executed', $diagnostics->moduleContributions);
        $this->assertArrayHasKey('skipped', $diagnostics->moduleContributions);
        $this->assertArrayHasKey('duration_ms', $diagnostics->moduleContributions);
        $this->assertArrayHasKey('modules', $diagnostics->moduleContributions);
    }

    public function test_audit_recorder_is_best_effort(): void
    {
        $recorder = Mockery::mock(AuditEventRecorder::class);
        $recorder->shouldReceive('record')->andThrow(new \RuntimeException('audit down'));

        $auditRecorder = new RuntimeContributionAuditRecorder($recorder);
        $context = $this->tenantContextWithDemo();

        $auditRecorder->recordContribution($context, 'demo');

        $this->assertTrue(true);
    }

    public function test_successful_contribution_records_audit_event(): void
    {
        $this->seedHeosPlatform();
        $context = $this->tenantContextWithDemo();

        app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::ModuleRuntimeContribution->value)
                ->exists(),
        );
    }

    public function test_snapshot_serializer_round_trips_extension_fields(): void
    {
        $this->seedHeosPlatform();
        $context = $this->tenantContextWithDemo();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);
        $serializer = app(RuntimeSnapshotSerializer::class);

        $payload = $serializer->serialize($runtime);
        $restored = $serializer->deserialize($payload);

        $this->assertSame($runtime->navigation, $restored->navigation);
        $this->assertSame($runtime->featureFlags, $restored->featureFlags);
        $this->assertSame($runtime->moduleDiagnostics, $restored->moduleDiagnostics);
        $this->assertSame($runtime->settingsMetadata, $restored->settingsMetadata);
        $this->assertSame($runtime->runtimeVersion, $restored->runtimeVersion);
    }

    public function test_legacy_snapshot_payload_deserializes_with_defaults(): void
    {
        $serializer = app(RuntimeSnapshotSerializer::class);

        $restored = $serializer->deserialize([
            'organization' => ['public_id' => 'org', 'name' => 'Org', 'slug' => 'org', 'status' => 'active'],
            'workspace' => ['public_id' => 'ws', 'name' => 'WS', 'slug' => 'ws', 'is_default' => true, 'status' => 'active'],
            'membership' => ['public_id' => 'mem', 'status' => 'active'],
            'active_applications' => [],
            'active_application' => null,
            'runtime_version' => 'hash',
            'settings_version' => 1,
            'runtime_metadata' => [],
            'capabilities' => [],
        ]);

        $this->assertSame([], $restored->navigation);
        $this->assertSame([], $restored->featureFlags);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-ext-'.uniqid()]);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = TenantContext::fromModels($user, $organization, $membership, $workspace);

        $demo = \App\Models\Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(\App\Services\Application\ApplicationInstallationService::class)
            ->install($context, $demo->public_id);
        app(\App\Services\WorkspaceApplication\WorkspaceApplicationService::class)
            ->enable($context, $orgInstall->public_id);

        return $context;
    }

    private function sampleManifest(): \App\Services\Runtime\Data\RuntimeManifest
    {
        return new \App\Services\Runtime\Data\RuntimeManifest(
            fingerprintApplications: [],
            applications: [],
            applicationsByPublicId: [],
        );
    }
}

class TestModuleRuntimeContext implements ModuleRuntimeContext
{
    public function organizationPublicId(): string
    {
        return 'org-public-id';
    }

    public function workspacePublicId(): ?string
    {
        return 'workspace-public-id';
    }
}

class TestRuntimeContributor implements RuntimeModuleContributor
{
    public function __construct(
        private readonly string $key,
        private readonly int $priority = 0,
        private readonly array $dependencies = [],
        private readonly bool $shouldThrow = false,
    ) {
    }

    public function moduleKey(): string
    {
        return $this->key;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function dependencyKeys(): array
    {
        return $this->dependencies;
    }

    public function contribute(ModuleRuntimeContext $context): RuntimeContribution
    {
        if ($this->shouldThrow) {
            throw new \RuntimeException('contributor failed');
        }

        return RuntimeContribution::empty($this->key, $this->priority);
    }
}

class MismatchedRuntimeContributor implements RuntimeModuleContributor
{
    public function moduleKey(): string
    {
        return 'expected-key';
    }

    public function priority(): int
    {
        return 0;
    }

    public function dependencyKeys(): array
    {
        return [];
    }

    public function contribute(ModuleRuntimeContext $context): RuntimeContribution
    {
        return RuntimeContribution::empty('wrong-key');
    }
}
