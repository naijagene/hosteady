<?php

namespace Tests\Feature\Services\Application;

use App\Enums\AuditAction;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Modules\Sdk\Application\Contracts\ApplicationRegistry;
use App\Modules\Sdk\Application\Contracts\ApplicationRuntime;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;
use App\Services\Application\ApplicationDevelopmentService;
use App\Services\Application\ApplicationRuntimeRegistryService;
use App\Services\Application\ApplicationRuntimeService;
use App\Services\Module\ModuleDoctorService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7ApplicationRuntimeTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_ApplicationStatus_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Application\Enums\ApplicationStatus $case) => $case->value, \App\Modules\Sdk\Application\Enums\ApplicationStatus::cases());
        $this->assertContains('registered', $cases);
        $this->assertContains('enabled', $cases);
        $this->assertContains('disabled', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_NavigationItemType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Application\Enums\NavigationItemType $case) => $case->value, \App\Modules\Sdk\Application\Enums\NavigationItemType::cases());
        $this->assertContains('group', $cases);
        $this->assertContains('item', $cases);
        $this->assertContains('divider', $cases);
        $this->assertContains('link', $cases);
        $this->assertContains('module', $cases);
    }

    public function test_WorkspaceStatus_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Application\Enums\WorkspaceStatus $case) => $case->value, \App\Modules\Sdk\Application\Enums\WorkspaceStatus::cases());
        $this->assertContains('active', $cases);
        $this->assertContains('inactive', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_ApplicationVisibility_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Application\Enums\ApplicationVisibility $case) => $case->value, \App\Modules\Sdk\Application\Enums\ApplicationVisibility::cases());
        $this->assertContains('public', $cases);
        $this->assertContains('private', $cases);
        $this->assertContains('organization', $cases);
        $this->assertContains('workspace', $cases);
    }

    public function test_ApplicationType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Application\Enums\ApplicationType $case) => $case->value, \App\Modules\Sdk\Application\Enums\ApplicationType::cases());
        $this->assertContains('core', $cases);
        $this->assertContains('business', $cases);
        $this->assertContains('custom', $cases);
        $this->assertContains('module', $cases);
    }

    public function test_ApplicationDefinition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationManifest_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationManifest::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationManifest::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationReference_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationReference::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationReference::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationRuntimeMetadata_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationWorkspace_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationWorkspace::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationWorkspace::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationMenu_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\NavigationMenu::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\NavigationMenu::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationGroup_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\NavigationGroup::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\NavigationGroup::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\NavigationItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\NavigationItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationBadge_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\NavigationBadge::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\NavigationBadge::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_NavigationRoute_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\NavigationRoute::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\NavigationRoute::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationStatistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ApplicationHealthReport_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Application\Data\ApplicationHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Application\Data\ApplicationHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_application_runtime_contract_bound(): void
    {
        $this->assertInstanceOf(ApplicationRuntimeService::class, app(ApplicationRuntime::class));
    }

    public function test_application_registry_contract_bound(): void
    {
        $this->assertInstanceOf(ApplicationRuntimeRegistryService::class, app(ApplicationRegistry::class));
    }

    public function test_application_runtime_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.application_runtime.enabled', true));
    }

    public function test_permission_catalog_has_application_runtime_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(125, Permission::query()->count());
        foreach (['application.read', 'application.manage', 'navigation.read'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_module_doctor_includes_application_runtime(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('application_runtime', $report->platformSummary['enterprise']);
    }

    public function test_register_application_persists_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $app = app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'demo.app',
            'name' => 'Demo App',
            'application_type' => 'business',
        ]));
        $this->assertSame('demo.app', $app->applicationKey);
        $this->assertNotEmpty($app->publicId);
    }

    public function test_list_applications_returns_registered_app(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $registered = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'listed.app',
            'name' => 'Listed App',
        ]));
        $apps = $service->listApplications($context);
        $this->assertTrue(collect($apps)->contains(fn ($app) => $app->publicId === $registered->publicId));
    }

    public function test_enable_application_updates_status(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $registered = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'enable.app',
            'name' => 'Enable App',
        ]));
        $enabled = $service->enable($context, $registered->publicId);
        $this->assertSame(ApplicationStatus::Enabled->value, $enabled->status);
    }

    public function test_disable_application_updates_status(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $registered = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'disable.app',
            'name' => 'Disable App',
        ]));
        $service->enable($context, $registered->publicId);
        $disabled = $service->disable($context, $registered->publicId);
        $this->assertSame(ApplicationStatus::Disabled->value, $disabled->status);
    }

    public function test_navigation_returns_menu_collection(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $menus = app(ApplicationDevelopmentService::class)->navigation($context);
        $this->assertIsArray($menus);
    }

    public function test_workspaces_returns_collection(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $workspaces = app(ApplicationDevelopmentService::class)->workspaces($context);
        $this->assertIsArray($workspaces);
    }

    public function test_runtime_metadata_includes_application_section(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $metadata = app(ApplicationDevelopmentService::class)->runtimeMetadata($context);
        $this->assertInstanceOf(ApplicationRuntimeMetadata::class, $metadata);
        $this->assertTrue($metadata->enabled);
    }

    public function test_workspace_runtime_includes_application_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $runtime = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class)->resolve($context);
        $this->assertArrayHasKey('application', $runtime->runtimeMetadata['enterprise'] ?? []);
        $this->assertTrue($runtime->capabilities['application_runtime'] ?? false);
    }

    public function test_health_report_with_migrated_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $report = app(ApplicationDevelopmentService::class)->health($context);
        $this->assertTrue($report->enabled);
        $this->assertTrue($report->healthy);
    }

    public function test_statistics_after_registration(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'stats.app',
            'name' => 'Stats App',
        ]));
        $this->assertGreaterThanOrEqual(1, $service->statistics($context)->registeredApps);
    }

    public function test_audit_on_register(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'audit.app',
            'name' => 'Audit App',
        ]));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApplicationRuntimeRegistered->value)->exists());
    }

    public function test_search_indexer_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Application\ApplicationSearchIndexer::class)->indexApplicationBestEffort(
            ApplicationDefinition::fromArray(['application_key' => 'search.app', 'name' => 'Search']),
            $context->organization->id,
            $context->workspace->id,
        );
        $this->assertTrue(true);
    }

    public function test_tenant_isolation_for_applications(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        app(ApplicationDevelopmentService::class)->register($contextA, ApplicationDefinition::fromArray([
            'application_key' => 'tenant.a',
            'name' => 'Tenant A',
        ]));
        app()->instance(TenantContext::class, $contextB);
        $apps = app(ApplicationDevelopmentService::class)->listApplications($contextB);
        $this->assertFalse(collect($apps)->contains(fn ($app) => $app->applicationKey === 'tenant.a'));
    }

    public function test_viewer_cannot_register_application(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ApplicationDevelopmentService::class)->register($viewer, ApplicationDefinition::fromArray([
            'application_key' => 'denied',
            'name' => 'Denied',
        ]));
    }

    public function test_member_can_read_applications(): void
    {
        $owner = $this->tenantContext();
        $member = $this->memberContext($owner);
        $this->assertTrue(app(\App\Services\Application\ApplicationPermissionBridge::class)->canRead($member));
    }

    public function test_api_list_applications(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/apps')
            ->assertOk();
    }

    public function test_api_register_application(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/application-runtime/register', [
                'application_key' => 'api.app',
                'name' => 'API App',
            ])
            ->assertCreated()
            ->assertJsonPath('data.application_key', 'api.app');
    }

    public function test_api_show_application(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $app = app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'show.app',
            'name' => 'Show App',
        ]));
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/apps/'.$app->publicId)
            ->assertOk()
            ->assertJsonPath('data.public_id', $app->publicId);
    }

    public function test_api_enable_application(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $app = app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'enable.api',
            'name' => 'Enable API',
        ]));
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/application-runtime/apps/'.$app->publicId.'/enable')
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Enabled->value);
    }

    public function test_api_navigation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/navigation')
            ->assertOk();
    }

    public function test_api_workspaces(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/workspaces')
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/apps');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_static_application_routes_resolve(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/navigation')
            ->assertOk();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/application-runtime/workspaces')
            ->assertOk();
    }

    public function test_business_module_base_provides_navigation_hooks(): void
    {
        $module = new class extends \App\Modules\Sdk\Development\BusinessModuleBase {
            protected string $moduleKey = 'demo.hooks';

            public function navigation(): array
            {
                return [['item_key' => 'home', 'label' => 'Home']];
            }
        };
        $this->assertNotEmpty($module->navigation());
        $this->assertNotEmpty($module->runtimeMetadata()['navigation']);
    }

    public function test_business_module_base_provides_menus_and_workspace(): void
    {
        $module = new class extends \App\Modules\Sdk\Development\BusinessModuleBase {
            protected string $moduleKey = 'demo.workspace';
        };
        $this->assertIsArray($module->menus());
        $this->assertSame('demo.workspace', $module->workspace()['module_key']);
        $this->assertSame('demo.workspace', $module->application()['application_key']);
    }

    public function test_duplicate_registration_throws(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $definition = ApplicationDefinition::fromArray(['application_key' => 'dup.app', 'name' => 'Dup']);
        $service->register($context, $definition);
        $this->expectException(\App\Modules\Sdk\Application\Exceptions\ApplicationRegistrationException::class);
        $service->register($context, $definition);
    }

    public function test_find_application_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $registered = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'find.app',
            'name' => 'Find App',
        ]));
        $found = $service->findApplication($context, $registered->publicId);
        $this->assertSame($registered->publicId, $found->publicId);
    }

    public function test_create_application_workspace(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $app = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'workspace.app',
            'name' => 'Workspace App',
        ]));
        $model = ApplicationRuntimeApp::query()->where('public_id', $app->publicId)->firstOrFail();
        $workspace = app(\App\Services\Application\WorkspaceManagerService::class)->createForApplication(
            $context,
            $model,
            'default',
            'Default Workspace',
        );
        $this->assertSame('default', $workspace->workspaceKey);
    }

    public function test_sync_navigation_from_module(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $app = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'nav.app',
            'name' => 'Nav App',
        ]));
        $model = ApplicationRuntimeApp::query()->where('public_id', $app->publicId)->firstOrFail();
        app(\App\Services\Application\NavigationBuilderService::class)->syncFromModule($context, $model, [[
            'menu_key' => 'main',
            'item_key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => ['name' => 'dashboard', 'path' => '/dashboard'],
        ]]);
        $this->assertGreaterThanOrEqual(1, $service->statistics($context)->navigationNodes);
    }

    public function test_audit_on_enable_and_disable(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $app = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'lifecycle.app',
            'name' => 'Lifecycle App',
        ]));
        $service->enable($context, $app->publicId);
        $service->disable($context, $app->publicId);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApplicationRuntimeEnabled->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApplicationRuntimeDisabled->value)->exists());
    }

    public function test_manager_can_manage_applications(): void
    {
        $owner = $this->tenantContext();
        $manager = $this->roleContext($owner, 'manager');
        $this->assertTrue(app(\App\Services\Application\ApplicationPermissionBridge::class)->canManage($manager));
        $this->assertTrue(app(\App\Services\Application\ApplicationPermissionBridge::class)->canReadNavigation($manager));
    }

    public function test_viewer_cannot_manage_applications(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        $this->assertFalse(app(\App\Services\Application\ApplicationPermissionBridge::class)->canManage($viewer));
    }

    public function test_api_disable_application(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $app = app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'disable.api',
            'name' => 'Disable API',
        ]));
        app(ApplicationDevelopmentService::class)->enable($context, $app->publicId);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/application-runtime/apps/'.$app->publicId.'/disable')
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Disabled->value);
    }

    public function test_api_viewer_cannot_register(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $this->withHeaders($this->tenantHeaders($viewer))
            ->postJson('/api/v1/tenant/application-runtime/register', [
                'application_key' => 'denied.api',
                'name' => 'Denied',
            ])
            ->assertForbidden();
    }

    public function test_runtime_cache_persists_payload(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(ApplicationDevelopmentService::class)->cacheRuntime($context, ['apps' => 1]);
        $this->assertTrue(\App\Models\ApplicationRuntime\ApplicationRuntimeCache::query()->where('cache_key', 'runtime.metadata')->exists());
    }

    public function test_manifest_service_returns_manifest(): void
    {
        $manifest = app(\App\Services\Application\ApplicationRuntimeManifestService::class)->manifest('demo.app');
        $this->assertSame('demo.app', $manifest->applicationKey);
    }

    public function test_enterprise_application_service_finds_by_key(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(ApplicationDevelopmentService::class)->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'provider.app',
            'name' => 'Provider App',
        ]));
        $found = app(\App\Services\Application\EnterpriseApplicationService::class)->application($context, 'provider.app');
        $this->assertSame('provider.app', $found->applicationKey);
    }

    public function test_health_service_assess_structure(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $assess = app(\App\Services\Application\ApplicationHealthService::class)->assess($context);
        $this->assertArrayHasKey('registered_apps', $assess);
        $this->assertArrayHasKey('enabled_apps', $assess);
        $this->assertArrayHasKey('status', $assess);
    }

    public function test_navigation_permission_filters_items(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $menus = app(\App\Services\Application\ApplicationPermissionBridge::class)->filterMenus($context, [
            \App\Modules\Sdk\Application\Data\NavigationMenu::fromArray([
                'menu_key' => 'main',
                'label' => 'Main',
                'groups' => [[
                    'group_key' => 'g1',
                    'label' => 'Group',
                    'sort_order' => 0,
                    'items' => [
                        ['item_key' => 'visible', 'label' => 'Visible', 'item_type' => 'item', 'sort_order' => 0],
                        ['item_key' => 'hidden', 'label' => 'Hidden', 'item_type' => 'item', 'sort_order' => 1, 'required_permission' => 'application.manage'],
                    ],
                    'metadata' => [],
                ]],
            ]),
        ]);
        $this->assertCount(1, $menus);
    }

    public function test_application_exception_hierarchy(): void
    {
        $this->assertInstanceOf(
            \App\Modules\Sdk\Application\Exceptions\ApplicationException::class,
            new \App\Modules\Sdk\Application\Exceptions\ApplicationRegistrationException('test'),
        );
    }

    public function test_enabled_apps_count_in_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(ApplicationDevelopmentService::class);
        $app = $service->register($context, ApplicationDefinition::fromArray([
            'application_key' => 'enabled.stats',
            'name' => 'Enabled Stats',
        ]));
        $service->enable($context, $app->publicId);
        $this->assertSame(1, $service->statistics($context)->enabledApps);
    }

    public function test_workspace_isolation_for_applications(): void
    {
        $owner = $this->tenantContext();
        $otherUser = $this->createActiveUser();
        $otherOrg = $this->provisionTestOrganization($otherUser, ['slug' => 'other-org-'.uniqid()]);
        $otherContext = $this->buildTenantContext($otherUser, $otherOrg);
        app()->instance(TenantContext::class, $owner);
        app(ApplicationDevelopmentService::class)->register($owner, ApplicationDefinition::fromArray([
            'application_key' => 'workspace.only',
            'name' => 'Workspace Only',
        ]));
        app()->instance(TenantContext::class, $otherContext);
        $this->assertEmpty(app(ApplicationDevelopmentService::class)->listApplications($otherContext));
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'application-runtime-'.uniqid()]);
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
