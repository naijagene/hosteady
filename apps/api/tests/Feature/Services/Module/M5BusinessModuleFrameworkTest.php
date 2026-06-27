<?php

namespace Tests\Feature\Services\Module;

use App\Enums\AuditAction;
use App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException;
use App\Models\AuditLog;
use App\Models\BusinessModule;
use App\Models\BusinessModuleInstallation;
use App\Models\Permission;
use App\Modules\Sdk\Development\Data\BusinessModuleHealthReport;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;
use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;
use App\Modules\Sdk\Development\Enums\BusinessModuleScaffoldTarget;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleScaffoldException;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleValidationException;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\Development\BusinessModuleHealthService;
use App\Services\Module\Development\BusinessModuleRegistryService;
use App\Services\Module\Development\BusinessModuleScaffolderService;
use App\Services\Module\Development\BusinessModuleValidatorService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5BusinessModuleFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private ?string $scaffoldModulePath = null;

    protected function tearDown(): void
    {
        if ($this->scaffoldModulePath !== null) {
            $this->deleteDirectory($this->scaffoldModulePath);
            $this->scaffoldModulePath = null;
        }

        parent::tearDown();
    }

    public function test_manifest_dto_roundtrip(): void
    {
        $manifest = BusinessModuleManifest::fromArray($this->sampleManifest('inventory.core'));

        $roundtrip = BusinessModuleManifest::fromArray($manifest->toArray());

        $this->assertSame('inventory.core', $roundtrip->moduleKey);
        $this->assertSame('Inventory Core', $roundtrip->name);
        $this->assertCount(1, $roundtrip->permissions);
    }

    public function test_reference_dto_json_serializes_public_id_only(): void
    {
        $reference = BusinessModuleReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000501',
            'module_key' => 'crm.leads',
            'name' => 'CRM Leads',
            'status' => 'registered',
            'type' => 'business',
            'version' => '1.0.0',
        ]);

        $payload = $reference->jsonSerialize();

        $this->assertArrayHasKey('public_id', $payload);
        $this->assertArrayNotHasKey('id', $payload);
    }

    public function test_validation_report_dto_serializes(): void
    {
        $report = BusinessModuleValidationReport::fromArray([
            'module_key' => 'hr.core',
            'valid' => false,
            'issues' => [[
                'code' => 'missing_name',
                'message' => 'Module name is required.',
                'severity' => 'error',
                'field' => 'name',
            ]],
        ]);

        $this->assertFalse($report->toArray()['valid']);
        $this->assertCount(1, $report->jsonSerialize()['issues']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new BusinessModuleHealthReport(
            enabled: true,
            registered: 2,
            installed: 1,
            warnings: ['No business modules are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
    }

    public function test_validator_accepts_valid_manifest(): void
    {
        $report = app(BusinessModuleValidatorService::class)->validate(
            BusinessModuleManifest::fromArray($this->sampleManifest('procurement.core')),
        );

        $this->assertTrue($report->valid);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleManifest('INVALID KEY');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(BusinessModuleValidationException::class);
        app(BusinessModuleValidatorService::class)->assertValid(BusinessModuleManifest::fromArray($data));
    }

    public function test_validator_rejects_invalid_semver(): void
    {
        $data = $this->sampleManifest('finance.core');
        $data['version'] = 'not-semver';

        $this->expectException(BusinessModuleValidationException::class);
        app(BusinessModuleValidatorService::class)->assertValid(BusinessModuleManifest::fromArray($data));
    }

    public function test_validator_rejects_duplicate_permission_keys(): void
    {
        $data = $this->sampleManifest('inventory.dup');
        $data['permissions'] = [
            ['key' => 'inventory.dup.read', 'name' => 'Read A'],
            ['key' => 'inventory.dup.read', 'name' => 'Read B'],
        ];

        $this->expectException(BusinessModuleValidationException::class);
        app(BusinessModuleValidatorService::class)->assertValid(BusinessModuleManifest::fromArray($data));
    }

    public function test_validator_rejects_invalid_route_name(): void
    {
        $data = $this->sampleManifest('inventory.routes');
        $data['routes'] = [[
            'name' => 'Invalid Route Name',
            'method' => 'GET',
            'uri' => '/records',
            'action' => 'index',
        ]];

        $this->expectException(BusinessModuleValidationException::class);
        app(BusinessModuleValidatorService::class)->assertValid(BusinessModuleManifest::fromArray($data));
    }

    public function test_validator_rejects_invalid_dependency_shape(): void
    {
        $data = $this->sampleManifest('inventory.deps');
        $data['dependencies'] = [''];

        $this->expectException(BusinessModuleValidationException::class);
        app(BusinessModuleValidatorService::class)->assertValid(BusinessModuleManifest::fromArray($data));
    }

    public function test_registry_registers_and_lists_modules(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $reference = app(BusinessModuleRegistryService::class)->register(
            BusinessModuleManifest::fromArray($this->sampleManifest('registry.module.'.uniqid())),
            $context->user->id,
            $context->membership->id,
        );

        $modules = app(BusinessModuleRegistryService::class)->all();

        $this->assertNotEmpty($modules);
        $this->assertSame($reference->publicId, $modules[0]->publicId);
    }

    public function test_install_enable_disable_uninstall_lifecycle(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(BusinessModuleDevelopmentService::class);

        $module = $service->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('lifecycle.module.'.uniqid())),
        );

        $installed = $service->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
            settings: ['mode' => 'standard'],
        ));
        $this->assertSame(BusinessModuleInstallStatus::Installed->value, $installed->status);

        $enabled = $service->enable($context, $installed->installationPublicId);
        $this->assertSame(BusinessModuleInstallStatus::Enabled->value, $enabled->status);

        $disabled = $service->disable($context, $installed->installationPublicId);
        $this->assertSame(BusinessModuleInstallStatus::Disabled->value, $disabled->status);

        $uninstalled = $service->uninstall($context, $installed->installationPublicId);
        $this->assertSame(BusinessModuleInstallStatus::Uninstalled->value, $uninstalled->status);
    }

    public function test_install_seeds_manifest_permissions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $key = 'perm.seed.'.preg_replace('/[^a-z0-9.]/', '', uniqid());
        $permissionKey = $key.'.records.read';

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest($key, $permissionKey)),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(Permission::query()->where('key', $permissionKey)->exists());
    }

    public function test_scaffold_command_generates_structure(): void
    {
        $moduleKey = 'scaffold-m5-'.preg_replace('/[^a-z0-9-]/', '', uniqid());
        $this->scaffoldModulePath = app_path('Modules/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')));

        $exitCode = Artisan::call('heos:make-business-module', [
            'key' => $moduleKey,
            '--name' => 'Scaffold M5',
            '--with-api' => true,
            '--with-tests' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->scaffoldModulePath.'/Config/manifest.php');
        $this->assertFileExists($this->scaffoldModulePath.'/Services/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')).'Service.php');
        $this->assertFileExists($this->scaffoldModulePath.'/Providers/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')).'ServiceProvider.php');
        $this->assertTrue(BusinessModule::query()->where('module_key', $moduleKey)->exists());
    }

    public function test_scaffold_rejects_existing_module_without_force(): void
    {
        $moduleKey = 'scaffold-dup-'.preg_replace('/[^a-z0-9-]/', '', uniqid());
        $this->scaffoldModulePath = app_path('Modules/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')));

        Artisan::call('heos:make-business-module', ['key' => $moduleKey]);
        $exitCode = Artisan::call('heos:make-business-module', ['key' => $moduleKey]);

        $this->assertSame(1, $exitCode);
    }

    public function test_scaffold_service_respects_force_flag(): void
    {
        $moduleKey = 'scaffold-force-'.preg_replace('/[^a-z0-9-]/', '', uniqid());
        $this->scaffoldModulePath = app_path('Modules/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')));

        $scaffolder = app(BusinessModuleScaffolderService::class);
        $scaffolder->scaffold(new BusinessModuleScaffoldRequest(
            moduleKey: $moduleKey,
            name: 'Force Module',
            type: 'business',
            targets: [BusinessModuleScaffoldTarget::Module->value],
            force: false,
        ));

        $result = $scaffolder->scaffold(new BusinessModuleScaffoldRequest(
            moduleKey: $moduleKey,
            name: 'Force Module Updated',
            type: 'business',
            targets: [BusinessModuleScaffoldTarget::Module->value],
            force: true,
        ));

        $this->assertSame($moduleKey, $result->moduleKey);
    }

    public function test_scaffold_service_throws_without_force_when_directory_exists(): void
    {
        $moduleKey = 'scaffold-block-'.preg_replace('/[^a-z0-9-]/', '', uniqid());
        $this->scaffoldModulePath = app_path('Modules/'.str_replace(['.', '-'], '', ucwords($moduleKey, '.-')));

        app(BusinessModuleScaffolderService::class)->scaffold(new BusinessModuleScaffoldRequest(
            moduleKey: $moduleKey,
            name: 'Blocked Module',
            type: 'business',
            targets: [BusinessModuleScaffoldTarget::Module->value],
        ));

        $this->expectException(BusinessModuleScaffoldException::class);
        app(BusinessModuleScaffolderService::class)->scaffold(new BusinessModuleScaffoldRequest(
            moduleKey: $moduleKey,
            name: 'Blocked Module',
            type: 'business',
            targets: [BusinessModuleScaffoldTarget::Module->value],
            force: false,
        ));
    }

    public function test_runtime_metadata_includes_business_modules(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('runtime.module.'.uniqid())),
        );

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['business_modules']);
        $this->assertArrayHasKey('business_modules', $runtime->runtimeMetadata['enterprise']);
        $this->assertTrue($runtime->runtimeMetadata['enterprise']['business_modules']['enabled']);
        $this->assertGreaterThanOrEqual(1, $runtime->runtimeMetadata['enterprise']['business_modules']['registered']);
    }

    public function test_disabled_capability_blocks_api_access(): void
    {
        config(['heos.enterprise.business_modules.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(EnterpriseCapabilityDisabledException::class);
        app(BusinessModuleDevelopmentService::class)->listModules($context);
    }

    public function test_doctor_exposes_business_modules_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('business_modules', $report->platformSummary['enterprise']);
        $this->assertArrayHasKey('enabled', $report->platformSummary['enterprise']['business_modules']);
        $this->assertArrayHasKey('registered', $report->platformSummary['enterprise']['business_modules']);
        $this->assertArrayHasKey('installed', $report->platformSummary['enterprise']['business_modules']);
        $this->assertArrayHasKey('warnings', $report->platformSummary['enterprise']['business_modules']);
        $this->assertArrayHasKey('status', $report->platformSummary['enterprise']['business_modules']);
    }

    public function test_health_service_warns_when_enabled_but_none_registered(): void
    {
        $health = app(BusinessModuleHealthService::class)->assess();

        $this->assertTrue($health['enabled']);
        $this->assertSame(0, $health['registered']);
        $this->assertNotEmpty($health['warnings']);
        $this->assertSame('warning', $health['status']);
    }

    public function test_health_service_reports_missing_tables(): void
    {
        Schema::drop('business_modules');

        $health = app(BusinessModuleHealthService::class)->assess();

        $this->assertSame('warning', $health['status']);
        $this->assertContains('business_modules', $health['missing_tables']);
    }

    public function test_search_indexing_is_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->mock(SearchIndexService::class, function ($mock) {
            $mock->shouldReceive('upsert')->andThrow(new \RuntimeException('search unavailable'));
        });

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('search.module.'.uniqid())),
        );

        $result = app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertSame(BusinessModuleInstallStatus::Installed->value, $result->status);
    }

    public function test_register_and_install_record_audit_actions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(BusinessModuleDevelopmentService::class);

        $module = $service->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('audit.module.'.uniqid())),
        );

        $service->install($context, new BusinessModuleInstallRequest(modulePublicId: $module->publicId));
        $service->enable($context, BusinessModuleInstallation::query()->firstOrFail()->public_id);
        $service->disable($context, BusinessModuleInstallation::query()->firstOrFail()->public_id);
        $service->uninstall($context, BusinessModuleInstallation::query()->firstOrFail()->public_id);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleRegistered->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleInstalled->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleEnabled->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleDisabled->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleUninstalled->value)->exists());
    }

    public function test_validate_manifest_records_audit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(BusinessModuleDevelopmentService::class)->validateManifest(
            BusinessModuleManifest::fromArray($this->sampleManifest('validated.module')),
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::BusinessModuleValidated->value)->exists());
    }

    public function test_member_can_read_but_not_manage(): void
    {
        $ownerContext = $this->tenantContext();
        app()->instance(TenantContext::class, $ownerContext);
        $memberContext = $this->memberContext($ownerContext);
        $service = app(BusinessModuleDevelopmentService::class);

        $service->registerModule(
            $ownerContext,
            BusinessModuleManifest::fromArray($this->sampleManifest('rbac.module.'.uniqid())),
        );

        $this->assertNotEmpty($service->listModules($memberContext));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->registerModule(
            $memberContext,
            BusinessModuleManifest::fromArray($this->sampleManifest('rbac.denied.'.uniqid())),
        );
    }

    public function test_manager_can_install(): void
    {
        $ownerContext = $this->tenantContext();
        app()->instance(TenantContext::class, $ownerContext);
        $managerContext = $this->managerContext($ownerContext);
        $service = app(BusinessModuleDevelopmentService::class);

        $module = $service->registerModule(
            $ownerContext,
            BusinessModuleManifest::fromArray($this->sampleManifest('manager.module.'.uniqid())),
        );

        $result = $service->install($managerContext, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertSame(BusinessModuleInstallStatus::Installed->value, $result->status);
    }

    public function test_viewer_cannot_install(): void
    {
        $ownerContext = $this->tenantContext();
        app()->instance(TenantContext::class, $ownerContext);
        $viewerContext = $this->viewerContext($ownerContext);
        $service = app(BusinessModuleDevelopmentService::class);

        $module = $service->registerModule(
            $ownerContext,
            BusinessModuleManifest::fromArray($this->sampleManifest('viewer.module.'.uniqid())),
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->install($viewerContext, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));
    }

    public function test_installations_are_isolated_by_organization(): void
    {
        $ownerA = $this->tenantContext();
        $ownerB = $this->tenantContext();
        app()->instance(TenantContext::class, $ownerA);
        $service = app(BusinessModuleDevelopmentService::class);

        $module = $service->registerModule(
            $ownerA,
            BusinessModuleManifest::fromArray($this->sampleManifest('tenant.module.'.uniqid())),
        );

        $install = $service->install($ownerA, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        app()->instance(TenantContext::class, $ownerB);
        $this->expectException(\App\Modules\Sdk\Development\Exceptions\BusinessModuleNotFoundException::class);
        $service->enable($ownerB, $install->installationPublicId);
    }

    public function test_installations_are_isolated_by_workspace(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(BusinessModuleDevelopmentService::class);

        $secondWorkspace = $context->organization->workspaces()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid7(),
            'name' => 'Secondary Workspace',
            'slug' => 'secondary-'.uniqid(),
            'is_default' => false,
            'status' => \App\Enums\WorkspaceStatus::Active,
        ]);

        $module = $service->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('workspace.module.'.uniqid())),
        );

        $install = $service->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $otherWorkspaceContext = TenantContext::fromModels(
            $context->user,
            $context->organization,
            $context->membership,
            $secondWorkspace,
        );

        app()->instance(TenantContext::class, $otherWorkspaceContext);
        $this->expectException(\App\Modules\Sdk\Development\Exceptions\BusinessModuleNotFoundException::class);
        $service->enable($otherWorkspaceContext, $install->installationPublicId);
    }

    public function test_api_list_modules_returns_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('api.list.'.uniqid())),
        );

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/business-modules');

        $response->assertOk();
        $payload = $response->json('data.0') ?? $response->json('0') ?? [];
        $this->assertArrayHasKey('public_id', $payload);
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_api_installed_route_is_registered_before_parameterized_route(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('api.installed.'.uniqid())),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/business-modules/installed');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data') ?? $response->json());
    }

    public function test_api_show_install_enable_disable_uninstall_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray($this->sampleManifest('api.flow.'.uniqid())),
        );

        $showResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/business-modules/'.$module->publicId);
        $showResponse->assertOk();

        $installResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/business-modules/'.$module->publicId.'/install', [
                'settings' => ['enabled' => true],
            ]);
        $installResponse->assertCreated();
        $installationPublicId = $installResponse->json('data.installation_public_id')
            ?? $installResponse->json('installation_public_id');
        $this->assertNotEmpty($installationPublicId);

        $enableResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/business-modules/installations/'.$installationPublicId.'/enable');
        $enableResponse->assertOk();
        $this->assertSame('enabled', $enableResponse->json('data.status') ?? $enableResponse->json('status'));

        $disableResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/business-modules/installations/'.$installationPublicId.'/disable');
        $disableResponse->assertOk();

        $deleteResponse = $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/business-modules/installations/'.$installationPublicId);
        $deleteResponse->assertOk();
        $this->assertSame('uninstalled', $deleteResponse->json('data.status') ?? $deleteResponse->json('status'));
    }

    public function test_permission_catalog_includes_business_module_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'business.modules.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'business.modules.install')->exists());
        $this->assertTrue(Permission::query()->where('key', 'business.modules.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'business.modules.develop')->exists());
        $this->assertPermissionCatalogComplete();
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'business-modules-'.uniqid()]);

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

    private function managerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'manager');
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

    /**
     * @return array<string, mixed>
     */
    private function sampleManifest(string $moduleKey, ?string $permissionKey = null): array
    {
        $permissionKey ??= $moduleKey.'.records.read';

        return [
            'module_key' => $moduleKey,
            'name' => ucwords(str_replace(['.', '-', '_'], ' ', $moduleKey)),
            'description' => 'Sample business module manifest.',
            'type' => 'business',
            'version' => '1.0.0',
            'capabilities' => [['key' => 'records', 'name' => 'Records']],
            'permissions' => [[
                'key' => $permissionKey,
                'name' => 'Read Records',
                'domain' => 'business',
            ]],
            'routes' => [[
                'name' => $moduleKey.'.records.index',
                'method' => 'GET',
                'uri' => '/records',
                'action' => 'index',
            ]],
            'entities' => [['key' => 'record', 'name' => 'Record']],
            'workflows' => [],
            'dependencies' => ['heos.core'],
            'settings' => ['default_mode' => 'standard'],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }
}
