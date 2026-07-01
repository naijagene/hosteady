<?php

namespace Tests\Feature\Seeders;

use App\Models\Application;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\AuditLog;
use App\Models\NavigationDefinition;
use App\Models\NavigationItem;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UiPage;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceApplication;
use App\Modules\HosteadyAdmin\HosteadyAdminModule;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Dashboard\DynamicDashboardDevelopmentService;
use App\Services\Form\DynamicFormDevelopmentService;
use App\Services\Report\DynamicReportDevelopmentService;
use App\Services\Table\DynamicTableDevelopmentService;
use App\Services\Ui\UiDevelopmentService;
use App\Support\Tenant\TenantContext;
use Database\Seeders\AlphaDemoSeeder;
use Database\Seeders\HosteadyAdminApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HosteadyAdminApplicationSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('ALPHA_DEMO_PASSWORD=alpha-test-placeholder');
        $_ENV['ALPHA_DEMO_PASSWORD'] = 'alpha-test-placeholder';
    }

    public function test_registers_hosteady_admin_catalog_application(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $application = Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->first();

        $this->assertNotNull($application);
        $this->assertSame(HosteadyAdminApplicationSeeder::CATALOG_MODULE_KEY, $application->key);
        $this->assertSame('Hosteady Admin', $application->name);
        $this->assertSame('0.1.0-alpha', $application->version);
        $this->assertSame('platform', $application->category);
    }

    public function test_syncs_hosteady_admin_module_into_catalog_for_doctor(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $application = Application::query()->where('key', HosteadyAdminApplicationSeeder::CATALOG_MODULE_KEY)->first();

        $this->assertNotNull($application);
        $this->assertSame(HosteadyAdminModule::MODULE_UUID, $application->module_uuid);
        $this->assertSame(
            1,
            Application::query()->where('module_uuid', HosteadyAdminModule::MODULE_UUID)->count(),
        );
    }

    public function test_installs_into_moondew_group_production_workspace(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->firstOrFail();
        $application = Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->firstOrFail();

        $this->assertNotNull(
            OrganizationApplication::query()
                ->where('organization_id', $organization->id)
                ->where('application_id', $application->id)
                ->whereNull('deleted_at')
                ->first(),
        );

        $this->assertNotNull(
            WorkspaceApplication::query()
                ->where('workspace_id', $workspace->id)
                ->whereHas('organizationApplication', fn ($query) => $query->where('application_id', $application->id))
                ->whereNull('deleted_at')
                ->first(),
        );
    }

    public function test_seeds_permissions_and_role_assignments(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        foreach (HosteadyAdminApplicationSeeder::PERMISSION_KEYS as $permissionKey) {
            $this->assertNotNull(Permission::query()->where('key', $permissionKey)->first());
        }

        $admin = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['admin']['email'])->firstOrFail();
        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->firstOrFail();

        $context = TenantContext::fromModels($admin, $organization, $membership, $workspace);
        $permissions = app(TenantAuthorizationService::class)->permissionsFor($context);

        foreach (HosteadyAdminApplicationSeeder::PERMISSION_KEYS as $permissionKey) {
            $this->assertContains($permissionKey, $permissions, "Missing [{$permissionKey}] for administrator.");
        }

        $viewer = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['viewer']['email'])->firstOrFail();
        $viewerMembership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $viewer->id)
            ->firstOrFail();
        $viewerContext = TenantContext::fromModels($viewer, $organization, $viewerMembership, $workspace);
        $viewerPermissions = app(TenantAuthorizationService::class)->permissionsFor($viewerContext);

        $this->assertContains('hosteady.admin.read', $viewerPermissions);
        $this->assertNotContains('hosteady.admin.manage', $viewerPermissions);
    }

    public function test_seeds_navigation_ui_and_artifacts(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        if (Schema::hasTable('navigation_definitions')) {
            $definition = NavigationDefinition::query()
                ->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)
                ->where('navigation_key', HosteadyAdminApplicationSeeder::NAVIGATION_KEY)
                ->first();

            $this->assertNotNull($definition);
            $this->assertSame('published', $definition->status);
            $this->assertGreaterThanOrEqual(
                9,
                NavigationItem::query()->where('navigation_definition_id', $definition->id)->count(),
            );
        }

        if (Schema::hasTable('ui_pages')) {
            $this->assertSame(
                9,
                UiPage::query()->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)->count(),
            );

            $overview = UiPage::query()
                ->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)
                ->where('page_key', 'overview')
                ->first();

            $this->assertNotNull($overview);
            $this->assertSame(
                '/app/'.HosteadyAdminApplicationSeeder::MODULE_KEY.'/overview',
                $overview->route_path,
            );
            $this->assertNotEmpty($overview->regions_json);
            $this->assertNotEmpty($overview->components_json);

            foreach (['overview', 'organizations', 'workspaces', 'applications', 'users', 'roles-permissions', 'runtime', 'activity-audit', 'reports'] as $pageKey) {
                $page = UiPage::query()
                    ->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)
                    ->where('page_key', $pageKey)
                    ->first();

                $this->assertNotNull($page, "Missing page [{$pageKey}]");
                $this->assertNotEmpty(
                    is_array($page->regions_json) ? $page->regions_json : [],
                    "Page [{$pageKey}] is missing regions.",
                );
            }
        }

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $admin = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['admin']['email'])->firstOrFail();
        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->firstOrFail();
        $context = TenantContext::fromModels($admin, $organization, $membership, $workspace);

        foreach (['organization-profile', 'workspace-profile', 'user-invite', 'application-settings'] as $formKey) {
            app(DynamicFormDevelopmentService::class)->findDefinition(
                $context,
                HosteadyAdminApplicationSeeder::MODULE_KEY,
                $formKey,
            );
        }

        foreach (['organizations', 'workspaces', 'applications', 'users', 'permissions', 'activity'] as $tableKey) {
            app(DynamicTableDevelopmentService::class)->findDefinition(
                $context,
                HosteadyAdminApplicationSeeder::MODULE_KEY,
                $tableKey,
            );
        }

        app(DynamicDashboardDevelopmentService::class)->findDefinition(
            $context,
            HosteadyAdminApplicationSeeder::MODULE_KEY,
            HosteadyAdminApplicationSeeder::DASHBOARD_KEY,
        );

        foreach (['platform-health', 'permission-coverage', 'activity-summary'] as $reportKey) {
            app(DynamicReportDevelopmentService::class)->findDefinition(
                $context,
                HosteadyAdminApplicationSeeder::MODULE_KEY,
                $reportKey,
            );
        }
    }

    public function test_seeds_notification_and_audit_samples_when_supported(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();

        if (Schema::hasTable('audit_logs')) {
            $this->assertTrue(
                AuditLog::query()
                    ->where('organization_id', $organization->id)
                    ->where('metadata->hosteady_admin_installed', true)
                    ->exists(),
            );
        } else {
            $this->markTestSkipped('audit_logs table missing.');
        }
    }

    public function test_is_idempotent_on_second_run(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $application = Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->firstOrFail();

        $this->assertSame(
            1,
            OrganizationApplication::query()
                ->where('organization_id', $organization->id)
                ->where('application_id', $application->id)
                ->whereNull('deleted_at')
                ->count(),
        );

        if (Schema::hasTable('ui_pages')) {
            $this->assertSame(
                9,
                UiPage::query()->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)->count(),
            );
        }
    }

    public function test_skips_when_alpha_tenant_missing(): void
    {
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $this->assertSame(0, Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->count());
    }

    public function test_manager_receives_read_reports_and_audit_permissions(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $manager = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['manager']['email'])->firstOrFail();
        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $manager->id)
            ->firstOrFail();
        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->firstOrFail();

        $context = TenantContext::fromModels($manager, $organization, $membership, $workspace);
        $permissions = app(TenantAuthorizationService::class)->permissionsFor($context);

        $this->assertContains('hosteady.admin.read', $permissions);
        $this->assertContains('hosteady.admin.reports.read', $permissions);
        $this->assertContains('hosteady.admin.audit.read', $permissions);
        $this->assertNotContains('hosteady.admin.manage', $permissions);
        $this->assertNotContains('hosteady.admin.configure', $permissions);
    }

    public function test_overview_render_payload_includes_regions_and_components(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();
        $admin = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['admin']['email'])->firstOrFail();
        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->firstOrFail();
        $context = TenantContext::fromModels($admin, $organization, $membership, $workspace);
        app()->instance(TenantContext::class, $context);

        $payload = app(UiDevelopmentService::class)->renderPage(
            $context,
            HosteadyAdminApplicationSeeder::MODULE_KEY,
            'overview',
        );
        $data = $payload->toArray();

        $this->assertNotEmpty($data['regions']);
        $this->assertNotEmpty($data['components']);
    }

    public function test_idempotent_seed_does_not_create_duplicate_catalog_or_generic_runtime_apps(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);
        $this->seed(HosteadyAdminApplicationSeeder::class);

        $this->assertSame(
            1,
            Application::query()->where('module_uuid', HosteadyAdminModule::MODULE_UUID)->count(),
        );
        $this->assertSame(
            0,
            Application::query()
                ->where('key', HosteadyAdminApplicationSeeder::APPLICATION_KEY)
                ->where('module_uuid', '!=', HosteadyAdminModule::MODULE_UUID)
                ->count(),
        );

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->firstOrFail();

        $runtimeApps = ApplicationRuntimeApp::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', HosteadyAdminApplicationSeeder::MODULE_KEY)
            ->get();

        $this->assertSame(1, $runtimeApps->count());
        $this->assertSame('Hosteady Admin', $runtimeApps->first()->name);
        $this->assertSame(HosteadyAdminApplicationSeeder::MODULE_KEY, $runtimeApps->first()->application_key);
    }
}
