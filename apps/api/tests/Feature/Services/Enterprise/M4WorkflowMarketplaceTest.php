<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Enums\WorkspaceStatus;
use App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException;
use App\Models\AuditLog;
use App\Models\WorkflowPackage as WorkflowPackageModel;
use App\Models\WorkflowPackageInstall;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowDependency;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowCompatibilityStatus;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageException;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageNotFoundException;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowCompatibilityService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowDependencyResolverService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowMarketplaceService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowPackageHealthService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowPackageProviderService;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowPackageValidatorService;
use App\Services\Enterprise\Workflow\WorkflowHealthService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4WorkflowMarketplaceTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_manifest_from_array_roundtrip(): void
    {
        $manifest = WorkflowPackageManifest::fromArray($this->sampleManifest());

        $this->assertSame('heos', $manifest->engine);
        $this->assertCount(2, $manifest->workflow['nodes'] ?? []);
        $this->assertNotEmpty($manifest->canvas['nodes'] ?? []);
    }

    public function test_dependency_serializes_to_array(): void
    {
        $dependency = new WorkflowDependency('workflow', 'capability', '>=1.0.0', true, ['note' => 'test']);

        $this->assertSame('workflow', $dependency->toArray()['key']);
        $this->assertSame('capability', $dependency->toArray()['type']);
    }

    public function test_compatibility_report_dto_serializes(): void
    {
        $report = new WorkflowCompatibilityReport(
            packagePublicId: '01900000-0000-7000-8000-000000000700',
            status: WorkflowCompatibilityStatus::Compatible->value,
            issues: [],
            warnings: ['Optional capability disabled.'],
            dependencies: [new WorkflowDependency('search', 'capability', null, false)],
        );

        $this->assertSame('compatible', $report->toArray()['status']);
        $this->assertCount(1, $report->toArray()['warnings']);
    }

    public function test_provider_normalizes_workflow_nodes_into_definition(): void
    {
        $provider = app(WorkflowPackageProviderService::class);
        $manifest = $provider->normalizeManifest(WorkflowPackageManifest::fromArray($this->sampleManifest()));

        $this->assertArrayHasKey('definition', $manifest->workflow);
        $this->assertCount(2, $manifest->workflow['definition']['nodes']);
        $this->assertCount(1, $manifest->workflow['definition']['transitions']);
    }

    public function test_validator_accepts_valid_manifest(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $manifest = WorkflowPackageManifest::fromArray($this->sampleManifest());

        $this->assertSame([], $validator->validate($manifest));
    }

    public function test_validator_rejects_invalid_package_key(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $data = $this->sampleManifest('INVALID KEY');
        $data['key'] = 'INVALID KEY';

        $this->expectException(WorkflowPackageException::class);
        $validator->assertValid(WorkflowPackageManifest::fromArray($data));
    }

    public function test_validator_rejects_invalid_semver(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $data = $this->sampleManifest();
        $data['version'] = 'not-a-version';

        $this->expectException(WorkflowPackageException::class);
        $validator->assertValid(WorkflowPackageManifest::fromArray($data));
    }

    public function test_validator_rejects_empty_workflow_and_canvas(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $data = $this->sampleManifest();
        unset($data['workflow'], $data['canvas']);

        $this->expectException(WorkflowPackageException::class);
        $validator->assertValid(WorkflowPackageManifest::fromArray($data));
    }

    public function test_validator_rejects_unsupported_engine(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $data = $this->sampleManifest();
        $data['engine'] = 'legacy';

        $this->expectException(WorkflowPackageException::class);
        $validator->assertValid(WorkflowPackageManifest::fromArray($data));
    }

    public function test_validator_rejects_incomplete_dependency(): void
    {
        $validator = app(WorkflowPackageValidatorService::class);
        $data = $this->sampleManifest();
        $data['requires'] = [['key' => '', 'type' => 'capability']];

        $this->expectException(WorkflowPackageException::class);
        $validator->assertValid(WorkflowPackageManifest::fromArray($data));
    }

    public function test_compatibility_reports_compatible_for_valid_manifest(): void
    {
        $context = $this->tenantContext();
        $scope = $this->enterpriseScope($context);
        $manifest = WorkflowPackageManifest::fromArray($this->sampleManifest());

        $report = app(WorkflowCompatibilityService::class)->assessManifest($scope, $manifest);

        $this->assertSame(WorkflowCompatibilityStatus::Compatible->value, $report->status);
    }

    public function test_compatibility_reports_unsupported_when_workflow_disabled(): void
    {
        config(['heos.enterprise.workflow.enabled' => false]);
        $context = $this->tenantContext();
        $scope = $this->enterpriseScope($context);

        $report = app(WorkflowCompatibilityService::class)->assessManifest(
            $scope,
            WorkflowPackageManifest::fromArray($this->sampleManifest()),
        );

        $this->assertSame(WorkflowCompatibilityStatus::Unsupported->value, $report->status);
        $this->assertNotEmpty($report->issues);
    }

    public function test_compatibility_reports_warning_for_optional_capability(): void
    {
        config(['heos.enterprise.search.enabled' => false]);
        $context = $this->tenantContext();
        $scope = $this->enterpriseScope($context);
        $data = $this->sampleManifest();
        $data['requires'] = [['key' => 'search', 'type' => 'capability', 'required' => false]];

        $report = app(WorkflowCompatibilityService::class)->assessManifest(
            $scope,
            WorkflowPackageManifest::fromArray($data),
        );

        $this->assertSame(WorkflowCompatibilityStatus::Warning->value, $report->status);
        $this->assertNotEmpty($report->warnings);
    }

    public function test_compatibility_reports_unsupported_when_designer_required_for_canvas(): void
    {
        config(['heos.enterprise.workflow_designer.enabled' => false]);
        $context = $this->tenantContext();
        $scope = $this->enterpriseScope($context);

        $report = app(WorkflowCompatibilityService::class)->assessManifest(
            $scope,
            WorkflowPackageManifest::fromArray($this->sampleManifest()),
        );

        $this->assertSame(WorkflowCompatibilityStatus::Unsupported->value, $report->status);
        $this->assertStringContainsString('designer', strtolower(implode(' ', $report->issues)));
    }

    public function test_dependency_resolver_delegates_to_compatibility_service(): void
    {
        $context = $this->tenantContext();
        $scope = $this->enterpriseScope($context);

        $report = app(WorkflowDependencyResolverService::class)->resolve(
            $scope,
            WorkflowPackageManifest::fromArray($this->sampleManifest()),
        );

        $this->assertSame(WorkflowCompatibilityStatus::Compatible->value, $report->status);
    }

    public function test_create_package_persists_draft_version(): void
    {
        $context = $this->tenantContext();
        $package = app(WorkflowMarketplaceService::class)->createPackage($context, $this->sampleManifest());

        $this->assertNotEmpty($package->publicId);
        $this->assertSame('draft', $package->status);
        $this->assertTrue(WorkflowPackageModel::query()->where('public_id', $package->publicId)->exists());
    }

    public function test_list_packages_returns_created_package(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $created = $service->createPackage($context, $this->sampleManifest('marketplace.list.'.uniqid()));

        $packages = $service->listPackages($context);

        $this->assertNotEmpty(array_filter($packages, fn ($p) => $p->publicId === $created->publicId));
    }

    public function test_show_package_returns_details(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $created = $service->createPackage($context, $this->sampleManifest('marketplace.show.'.uniqid()));

        $shown = $service->showPackage($context, $created->publicId);

        $this->assertSame($created->publicId, $shown->publicId);
        $this->assertSame($created->packageKey, $shown->packageKey);
    }

    public function test_publish_version_marks_published(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.publish.'.uniqid());
        $package = $service->createPackage($context, $manifest);

        $version = $service->publishVersion($context, $package->publicId, ['version' => $manifest['version']]);

        $this->assertSame('published', $version->status);
        $this->assertSame('published', $service->showPackage($context, $package->publicId)->status);
    }

    public function test_create_package_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(WorkflowMarketplaceService::class)->createPackage($context, $this->sampleManifest('marketplace.audit.create.'.uniqid()));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplacePackageCreated->value)->exists());
    }

    public function test_publish_version_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.audit.publish.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => $manifest['version']]);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplacePackageVersionPublished->value)->exists());
    }

    public function test_export_package_returns_heos_payload(): void
    {
        $context = $this->tenantContext();
        $package = $this->createPublishedPackage($context, 'marketplace.export.'.uniqid());

        $export = app(WorkflowMarketplaceService::class)->exportPackage($context, $package->publicId);

        $this->assertSame('heos_package', $export['format']);
        $this->assertNotEmpty($export['manifest']);
        $this->assertNotEmpty($export['package']['package_key'] ?? null);
    }

    public function test_export_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $package = $this->createPublishedPackage($context, 'marketplace.audit.export.'.uniqid());

        app(WorkflowMarketplaceService::class)->exportPackage($context, $package->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceExported->value)->exists());
    }

    public function test_import_package_creates_draft(): void
    {
        $context = $this->tenantContext();
        $source = $this->createPublishedPackage($context, 'marketplace.import.source.'.uniqid());
        $export = app(WorkflowMarketplaceService::class)->exportPackage($context, $source->publicId);

        $imported = app(WorkflowMarketplaceService::class)->importPackage($context, $export);

        $this->assertSame('draft', $imported->status);
        $this->assertNotSame($source->publicId, $imported->publicId);
    }

    public function test_import_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $source = $this->createPublishedPackage($context, 'marketplace.audit.import.'.uniqid());
        $export = app(WorkflowMarketplaceService::class)->exportPackage($context, $source->publicId);

        app(WorkflowMarketplaceService::class)->importPackage($context, $export);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceImported->value)->exists());
    }

    public function test_install_package_creates_workflow_definition(): void
    {
        $context = $this->tenantContext();
        $package = $this->createPublishedPackage($context, 'marketplace.install.'.uniqid());

        $result = app(WorkflowMarketplaceService::class)->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $this->assertSame('installed', $result->status);
        $this->assertNotEmpty($result->installedWorkflowDefinitionPublicId);
        $this->assertTrue(WorkflowPackageInstall::query()->where('public_id', $result->installPublicId)->exists());
    }

    public function test_install_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $package = $this->createPublishedPackage($context, 'marketplace.audit.install.'.uniqid());

        app(WorkflowMarketplaceService::class)->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceInstalled->value)->exists());
    }

    public function test_list_installed_returns_install(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $package = $this->createPublishedPackage($context, 'marketplace.installed.'.uniqid());
        $installed = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $installs = $service->listInstalled($context);

        $this->assertNotEmpty(array_filter($installs, fn ($i) => $i->installPublicId === $installed->installPublicId));
    }

    public function test_upgrade_install_bumps_version(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.upgrade.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => '1.0.0']);
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
            targetVersion: '1.0.0',
        ));

        $manifest['version'] = '1.1.0';
        $service->publishVersion($context, $package->publicId, $manifest);

        $upgraded = $service->upgradeInstall($context, $install->installPublicId, ['target_version' => '1.1.0']);

        $this->assertSame('1.1.0', $upgraded->installedVersion);
        $this->assertSame('1.0.0', $upgraded->previousVersion);
    }

    public function test_upgrade_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.audit.upgrade.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => '1.0.0']);
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
            targetVersion: '1.0.0',
        ));
        $manifest['version'] = '1.1.0';
        $service->publishVersion($context, $package->publicId, $manifest);

        $service->upgradeInstall($context, $install->installPublicId, ['target_version' => '1.1.0']);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceUpgraded->value)->exists());
    }

    public function test_rollback_install_restores_previous_version(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.rollback.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => '1.0.0']);
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
            targetVersion: '1.0.0',
        ));
        $manifest['version'] = '1.1.0';
        $service->publishVersion($context, $package->publicId, $manifest);
        $service->upgradeInstall($context, $install->installPublicId, ['target_version' => '1.1.0']);

        $rolledBack = $service->rollbackInstall($context, $install->installPublicId);

        $this->assertSame('1.0.0', $rolledBack->restoredVersion);
        $this->assertSame('rolled_back', $rolledBack->status);
    }

    public function test_rollback_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.audit.rollback.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => '1.0.0']);
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
            targetVersion: '1.0.0',
        ));
        $manifest['version'] = '1.1.0';
        $service->publishVersion($context, $package->publicId, $manifest);
        $service->upgradeInstall($context, $install->installPublicId, ['target_version' => '1.1.0']);

        $service->rollbackInstall($context, $install->installPublicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceRollback->value)->exists());
    }

    public function test_uninstall_marks_install_uninstalled(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $package = $this->createPublishedPackage($context, 'marketplace.uninstall.'.uniqid());
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $result = $service->uninstallPackage($context, $install->installPublicId);

        $this->assertSame('uninstalled', $result->status);
    }

    public function test_uninstall_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(WorkflowMarketplaceService::class);
        $package = $this->createPublishedPackage($context, 'marketplace.audit.uninstall.'.uniqid());
        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $service->uninstallPackage($context, $install->installPublicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceUninstalled->value)->exists());
    }

    public function test_list_updates_detects_newer_version(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.updates.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => '1.0.0']);
        $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
            targetVersion: '1.0.0',
        ));
        $manifest['version'] = '1.2.0';
        $service->publishVersion($context, $package->publicId, $manifest);

        $updates = $service->listUpdates($context);

        $this->assertNotEmpty(array_filter($updates, fn ($p) => $p->publicId === $package->publicId));
    }

    public function test_check_compatibility_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $package = $this->createPublishedPackage($context, 'marketplace.compat.audit.'.uniqid());

        app(WorkflowMarketplaceService::class)->checkCompatibility($context, $package->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowMarketplaceCompatibilityChecked->value)->exists());
    }

    public function test_marketplace_health_service_reports_status(): void
    {
        $context = $this->tenantContext();
        $this->createPublishedPackage($context, 'marketplace.health.'.uniqid());

        $health = app(WorkflowPackageHealthService::class)->assess($context);

        $this->assertTrue($health['enabled']);
        $this->assertGreaterThan(0, $health['packages']);
        $this->assertArrayHasKey('installs', $health);
    }

    public function test_workflow_health_includes_marketplace_key(): void
    {
        $context = $this->tenantContext();
        $health = app(WorkflowHealthService::class)->assess($context);

        $this->assertArrayHasKey('marketplace', $health);
        $this->assertTrue($health['marketplace']['enabled']);
    }

    public function test_runtime_manifest_includes_marketplace(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['workflow_marketplace']);
        $this->assertArrayHasKey('marketplace', $runtime->runtimeMetadata['enterprise']['workflow']);
    }

    public function test_doctor_exposes_marketplace_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('marketplace', $report->platformSummary['enterprise']['workflow']);
    }

    public function test_member_can_read_and_install(): void
    {
        $ownerContext = $this->tenantContext();
        $package = $this->createPublishedPackage($ownerContext, 'marketplace.member.'.uniqid());
        $memberContext = $this->memberContext($ownerContext);
        $service = app(WorkflowMarketplaceService::class);

        $this->assertNotEmpty($service->listPackages($memberContext));
        $result = $service->installPackage($memberContext, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));
        $this->assertSame('installed', $result->status);
    }

    public function test_member_cannot_create_package(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkflowMarketplaceService::class)->createPackage($memberContext, $this->sampleManifest('marketplace.member.denied.'.uniqid()));
    }

    public function test_viewer_cannot_install(): void
    {
        $ownerContext = $this->tenantContext();
        $package = $this->createPublishedPackage($ownerContext, 'marketplace.viewer.'.uniqid());
        $viewerContext = $this->viewerContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkflowMarketplaceService::class)->installPackage($viewerContext, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));
    }

    public function test_tenant_isolation_prevents_cross_organization_access(): void
    {
        $ownerA = $this->tenantContext();
        $package = $this->createPublishedPackage($ownerA, 'marketplace.tenant.a.'.uniqid());

        $ownerB = $this->tenantContext();

        $this->expectException(WorkflowPackageNotFoundException::class);
        app(WorkflowMarketplaceService::class)->showPackage($ownerB, $package->publicId);
    }

    public function test_workspace_isolation_prevents_cross_workspace_access(): void
    {
        $contextA = $this->tenantContext();
        $service = app(WorkflowMarketplaceService::class);
        $package = $this->createPublishedPackage($contextA, 'marketplace.workspace.a.'.uniqid());
        $install = $service->installPackage($contextA, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $secondaryWorkspace = Workspace::query()->create([
            'organization_id' => $contextA->organization->id,
            'name' => 'Secondary',
            'slug' => 'secondary-'.uniqid(),
            'is_default' => false,
            'status' => WorkspaceStatus::Active,
        ]);

        $contextB = TenantContext::fromModels(
            $contextA->user,
            $contextA->organization,
            $contextA->membership,
            $secondaryWorkspace,
        );

        $this->expectException(WorkflowPackageNotFoundException::class);
        $service->rollbackInstall($contextB, $install->installPublicId);
    }

    public function test_marketplace_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.workflow_marketplace.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(EnterpriseCapabilityDisabledException::class);
        app(WorkflowMarketplaceService::class)->listPackages($context);
    }

    public function test_marketplace_health_reports_missing_packages_table(): void
    {
        Schema::dropIfExists('workflow_packages');

        $health = app(WorkflowPackageHealthService::class)->assess($this->tenantContext());

        $this->assertSame('warning', $health['status']);
        $this->assertContains('workflow_packages', $health['missing_tables']);
        $this->assertStringContainsString('Run php artisan migrate.', $health['warnings'][0]);
    }

    public function test_search_indexing_is_best_effort_for_marketplace_operations(): void
    {
        $this->mock(SearchIndexService::class, function ($mock): void {
            $mock->shouldReceive('upsert')->andThrow(new \RuntimeException('search unavailable'));
        });

        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest('marketplace.search.'.uniqid());
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => $manifest['version']]);

        $install = $service->installPackage($context, new WorkflowInstallRequest(
            packagePublicId: $package->publicId,
        ));

        $this->assertSame('installed', $install->status);
        $export = $service->exportPackage($context, $package->publicId);
        $imported = $service->importPackage($context, $export);
        $this->assertSame('draft', $imported->status);
    }

    public function test_api_list_marketplace_endpoint(): void
    {
        $context = $this->tenantContext();
        $this->createPublishedPackage($context, 'marketplace.api.list.'.uniqid());

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/workflows/marketplace');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data') ?? $response->json());
    }

    public function test_api_create_and_install_flow(): void
    {
        $context = $this->tenantContext();
        $manifest = $this->sampleManifest('marketplace.api.flow.'.uniqid());

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/workflows/marketplace', $manifest);

        $createResponse->assertCreated();
        $packagePublicId = $createResponse->json('data.public_id') ?? $createResponse->json('public_id');
        $this->assertNotEmpty($packagePublicId);

        $publishResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson("/api/v1/tenant/workflows/marketplace/{$packagePublicId}/versions", [
                'version' => $manifest['version'],
            ]);

        $publishResponse->assertCreated();

        $installResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson("/api/v1/tenant/workflows/marketplace/{$packagePublicId}/install");

        $installResponse->assertCreated();
        $this->assertSame('installed', $installResponse->json('data.status') ?? $installResponse->json('status'));
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'marketplace-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        $member = $this->createActiveUser();
        $memberRole = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $member,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        $viewer = $this->createActiveUser();
        $viewerRole = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'viewer')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $viewer->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $viewerRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $viewer,
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
        $token = $this->issueToken($context->user);

        return [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }

    private function enterpriseScope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleManifest(?string $packageKey = null): array
    {
        $key = $packageKey ?? 'marketplace.pkg.'.preg_replace('/[^a-z0-9]/', '', uniqid());

        return [
            'package_key' => $key,
            'key' => $key,
            'name' => 'Marketplace Test Package',
            'version' => '1.0.0',
            'description' => 'HEOS workflow marketplace test package.',
            'author' => 'HEOS Tests',
            'license' => 'MIT',
            'engine' => 'heos',
            'visibility' => 'organization',
            'type' => 'solution',
            'tags' => ['test', 'marketplace'],
            'workflow' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'transitions' => [
                    ['id' => 't1', 'source' => 'start', 'target' => 'end'],
                ],
                'triggers' => [
                    ['type' => 'manual'],
                ],
            ],
            'canvas' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start', 'label' => 'Start', 'x' => 0, 'y' => 100, 'width' => 80, 'height' => 80],
                    ['id' => 'end', 'type' => 'end', 'label' => 'End', 'x' => 200, 'y' => 100, 'width' => 80, 'height' => 80],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => 'start', 'target' => 'end', 'label' => 'Next'],
                ],
                'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
                'metadata' => ['designer_version' => '1.0'],
            ],
        ];
    }

    private function createPublishedPackage(TenantContext $context, ?string $packageKey = null): \App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage
    {
        $service = app(WorkflowMarketplaceService::class);
        $manifest = $this->sampleManifest($packageKey);
        $package = $service->createPackage($context, $manifest);
        $service->publishVersion($context, $package->publicId, ['version' => $manifest['version']]);

        return $service->showPackage($context, $package->publicId);
    }
}
