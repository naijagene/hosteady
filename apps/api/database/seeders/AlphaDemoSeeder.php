<?php

namespace Database\Seeders;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Exceptions\Application\ApplicationAlreadyInstalledException;
use App\Exceptions\WorkspaceApplication\DuplicateWorkspaceApplicationException;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UiPage;
use App\Models\User;
use App\Models\Workspace;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Dashboard\DynamicDashboardDevelopmentService;
use App\Services\Document\EnterpriseDocumentDevelopmentService;
use App\Services\Form\DynamicFormDevelopmentService;
use App\Services\Navigation\NavigationDevelopmentService;
use App\Services\Notification\NotificationDevelopmentService;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\OrganizationProvisioningService;
use App\Services\Personalization\PersonalizationRegistryService;
use App\Services\Personalization\PreferenceService;
use App\Services\Report\DynamicReportDevelopmentService;
use App\Services\Table\DynamicTableDevelopmentService;
use App\Services\Theme\ThemeDevelopmentService;
use App\Services\Ui\UiDevelopmentService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlphaDemoSeeder extends Seeder
{
    public const ORGANIZATION_SLUG = 'moondew-group';

    public const ORGANIZATION_NAME = 'Moondew Group';

    public const WORKSPACE_NAME = 'Production';

    public const WORKSPACE_SLUG = 'production';

    public const MODULE_KEY = 'alpha.preview';

    public const THEME_KEY = 'alpha-default';

    public const NAVIGATION_KEY = 'alpha-primary';

    public const PAGE_KEY = 'home';

    public const FORM_KEY = 'sample';

    public const TABLE_KEY = 'sample';

    public const DASHBOARD_KEY = 'sample';

    public const REPORT_KEY = 'sample';

    public const SAMPLE_APPLICATION_KEY = 'demo';

    public const SAMPLE_APPLICATION_DISPLAY_NAME = 'Hosteady Platform Preview';

    /**
     * @var array<string, array{display_name: string, email: string, role_key: string}>
     */
    public const DEMO_USERS = [
        'admin' => [
            'display_name' => 'BIGJYDE',
            'email' => 'bigjyde@alpha.demo.local',
            'role_key' => 'administrator',
        ],
        'manager' => [
            'display_name' => 'Alpha Manager',
            'email' => 'manager@alpha.demo.local',
            'role_key' => 'manager',
        ],
        'viewer' => [
            'display_name' => 'Alpha Viewer',
            'email' => 'viewer@alpha.demo.local',
            'role_key' => 'viewer',
        ],
    ];

    public function run(): void
    {
        $password = $this->resolveDemoPassword();

        if ($password === null) {
            $this->command?->error('Set ALPHA_DEMO_PASSWORD in apps/api/.env before running AlphaDemoSeeder.');
            $this->command?->line('Example: ALPHA_DEMO_PASSWORD=your-local-placeholder');

            return;
        }

        $this->ensurePlatformBootstrap();
        $this->renameSampleApplication();

        $context = DB::transaction(function () use ($password) {
            $organization = $this->ensureOrganization($password);
            $workspace = $this->ensureWorkspace($organization, $password);
            $adminUser = $this->ensureDemoUser(self::DEMO_USERS['admin'], $password);
            $adminMembership = $this->ensureMembership($organization, $adminUser, $workspace, $adminUser->id);
            $this->ensureRoleAssignment($organization, $adminMembership, self::DEMO_USERS['admin']['role_key'], $adminUser->id);

            $managerUser = $this->ensureDemoUser(self::DEMO_USERS['manager'], $password);
            $viewerUser = $this->ensureDemoUser(self::DEMO_USERS['viewer'], $password);

            $managerMembership = $this->ensureMembership($organization, $managerUser, $workspace, $adminUser->id);
            $viewerMembership = $this->ensureMembership($organization, $viewerUser, $workspace, $adminUser->id);

            $this->ensureRoleAssignment($organization, $managerMembership, self::DEMO_USERS['manager']['role_key'], $adminUser->id);
            $this->ensureRoleAssignment($organization, $viewerMembership, self::DEMO_USERS['viewer']['role_key'], $adminUser->id);

            $this->ensureAdminConsolePermissions($organization, $adminUser->id);

            $context = TenantContext::fromModels($adminUser, $organization, $adminMembership, $workspace);
            app()->instance(TenantContext::class, $context);

            $this->ensureSampleApplication($context);

            return $context;
        });

        $this->ensureExperienceMetadata($context);
        $this->ensureEnterpriseSamples($context);

        $this->command?->info('Alpha demo seed complete.');
        $this->command?->line('Organization: '.self::ORGANIZATION_NAME.' ('.$context->organization->public_id.')');
        $this->command?->line('Workspace: '.self::WORKSPACE_NAME.' ('.$context->workspace?->public_id.')');
        $this->command?->line('Administrator: '.self::DEMO_USERS['admin']['email'].' (BIGJYDE)');
    }

    private function ensurePlatformBootstrap(): void
    {
        $this->call(PlatformBootstrapSeeder::class);
    }

    private function renameSampleApplication(): void
    {
        Application::query()
            ->where('key', self::SAMPLE_APPLICATION_KEY)
            ->update(['name' => self::SAMPLE_APPLICATION_DISPLAY_NAME]);
    }

    private function ensureOrganization(string $password): Organization
    {
        $existing = Organization::query()
            ->where('slug', self::ORGANIZATION_SLUG)
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            $this->command?->info('Organization Moondew Group already exists — verifying workspace/users.');

            return $existing;
        }

        $adminUser = $this->ensureDemoUser(self::DEMO_USERS['admin'], $password);
        $provisioned = app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
            creatorUserId: $adminUser->id,
            name: self::ORGANIZATION_NAME,
            slug: self::ORGANIZATION_SLUG,
            timezone: 'UTC',
            locale: 'en',
            planTier: 'business',
            organizationCode: 'MOONDEW',
        ));

        return Organization::query()->where('public_id', $provisioned->organizationPublicId)->firstOrFail();
    }

    private function ensureWorkspace(Organization $organization, string $password): Workspace
    {
        $workspace = $organization->workspaces()
            ->where('slug', self::WORKSPACE_SLUG)
            ->whereNull('deleted_at')
            ->first();

        if ($workspace !== null) {
            return $workspace;
        }

        $defaultWorkspace = $organization->workspaces()
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->first();

        $actorId = User::query()->where('email', self::DEMO_USERS['admin']['email'])->value('id')
            ?? $this->ensureDemoUser(self::DEMO_USERS['admin'], $password)->id;

        if ($defaultWorkspace !== null) {
            $defaultWorkspace->fill([
                'name' => self::WORKSPACE_NAME,
                'slug' => self::WORKSPACE_SLUG,
            ]);
            $defaultWorkspace->applyAuditActor($actorId)->save();

            return $defaultWorkspace->fresh();
        }

        $workspace = new Workspace([
            'organization_id' => $organization->id,
            'name' => self::WORKSPACE_NAME,
            'slug' => self::WORKSPACE_SLUG,
            'is_default' => true,
            'status' => 'active',
        ]);
        $workspace->applyAuditActor($actorId)->save();

        return $workspace;
    }

    /**
     * @param  array{display_name: string, email: string, role_key: string}  $definition
     */
    private function ensureDemoUser(array $definition, string $password): User
    {
        $existing = User::query()->where('email', $definition['email'])->first();

        if ($existing !== null) {
            return $existing;
        }

        return User::query()->create([
            'public_id' => (string) Str::uuid7(),
            'name' => $definition['display_name'],
            'display_name' => $definition['display_name'],
            'email' => $definition['email'],
            'email_verified_at' => now(),
            'password' => $password,
            'status' => 'active',
        ]);
    }

    private function resolveDemoPassword(): ?string
    {
        $password = $_ENV['ALPHA_DEMO_PASSWORD'] ?? getenv('ALPHA_DEMO_PASSWORD');

        if (! is_string($password) || $password === '') {
            return null;
        }

        return $password;
    }

    private function ensureMembership(
        Organization $organization,
        User $user,
        Workspace $workspace,
        int $actorUserId,
    ): OrganizationMembership {
        $existing = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            if ($existing->default_workspace_id !== $workspace->id) {
                $existing->fill(['default_workspace_id' => $workspace->id]);
                $existing->applyAuditActor($actorUserId)->save();
            }

            return $existing;
        }

        $membership = new OrganizationMembership([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'invited_by_user_id' => $actorUserId,
            'join_method' => JoinMethod::System,
        ]);
        $membership->applyAuditActor($actorUserId)->save();

        return $membership;
    }

    private function ensureRoleAssignment(
        Organization $organization,
        OrganizationMembership $membership,
        string $roleKey,
        int $actorUserId,
    ): void {
        $role = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', $roleKey)
            ->where('is_system', true)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $exists = OrganizationMemberRole::query()
            ->where('organization_membership_id', $membership->id)
            ->where('role_id', $role->id)
            ->exists();

        if ($exists) {
            return;
        }

        OrganizationMemberRole::query()->create([
            'organization_membership_id' => $membership->id,
            'role_id' => $role->id,
            'created_at' => now(),
            'created_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'updated_by_user_id' => $actorUserId,
        ]);
    }

    /**
     * @var list<string>
     */
    private const ADMIN_CONSOLE_PERMISSIONS = [
        'platform.read',
        'permissions.read',
        'runtime.read',
        'settings.read',
        'diagnostics.read',
    ];

    private function ensureAdminConsolePermissions(Organization $organization, int $actorUserId): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('key', self::ADMIN_CONSOLE_PERMISSIONS)
            ->get()
            ->keyBy('key');

        if ($permissions->count() !== count(self::ADMIN_CONSOLE_PERMISSIONS)) {
            $this->warnSection(
                'admin permissions',
                'Administration permission catalog is incomplete — run PermissionCatalogSeeder.',
            );

            return;
        }

        foreach (['owner', 'administrator', 'manager'] as $roleKey) {
            $role = Role::query()
                ->where('organization_id', $organization->id)
                ->where('key', $roleKey)
                ->where('is_system', true)
                ->whereNull('deleted_at')
                ->first();

            if ($role === null) {
                continue;
            }

            foreach (self::ADMIN_CONSOLE_PERMISSIONS as $permissionKey) {
                $permission = $permissions->get($permissionKey);

                if ($permission === null) {
                    continue;
                }

                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'created_by_user_id' => $actorUserId,
                ]);
            }
        }
    }

    private function ensureSampleApplication(TenantContext $context): void
    {
        $application = Application::query()->where('key', self::SAMPLE_APPLICATION_KEY)->firstOrFail();

        $organizationApplication = OrganizationApplication::query()
            ->where('organization_id', $context->organization->id)
            ->where('application_id', $application->id)
            ->where('status', '!=', 'uninstalled')
            ->whereNull('deleted_at')
            ->first();

        if ($organizationApplication === null) {
            try {
                $organizationApplication = app(ApplicationInstallationService::class)->install(
                    $context,
                    $application->public_id,
                );
            } catch (ApplicationAlreadyInstalledException) {
                $organizationApplication = OrganizationApplication::query()
                    ->where('organization_id', $context->organization->id)
                    ->where('application_id', $application->id)
                    ->whereNull('deleted_at')
                    ->firstOrFail();
            }
        }

        try {
            app(WorkspaceApplicationService::class)->enable(
                $context,
                $organizationApplication->public_id,
                isBootstrap: false,
            );
        } catch (DuplicateWorkspaceApplicationException) {
            // Already enabled for workspace.
        }
    }

    private function ensureExperienceMetadata(TenantContext $context): void
    {
        $this->ensureTheme($context);
        $this->ensurePersonalization($context);
        $this->ensureNavigation($context);
        $this->ensureUiMetadata($context);
        $this->ensureFormDefinition($context);
        $this->ensureTableDefinition($context);
        $this->ensureDashboardDefinition($context);
        $this->ensureReportDefinition($context);
    }

    private function ensureEnterpriseSamples(TenantContext $context): void
    {
        $this->ensureDocumentSample($context);
        $this->ensureNotificationSample($context);
        $this->ensureAuditSample($context);
        $this->ensureWorkflowSample($context);
    }

    private function ensureTheme(TenantContext $context): void
    {
        if (! Schema::hasTable('theme_definitions')) {
            $this->warnSection('theme', 'theme_definitions table missing — run migrations first.');

            return;
        }

        $this->runSection('theme', function () use ($context) {
            $service = app(ThemeDevelopmentService::class);
            $theme = collect($service->listDefinitions($context))->first(
                fn ($definition) => $definition->moduleKey === self::MODULE_KEY && $definition->themeKey === self::THEME_KEY,
            );

            if ($theme === null) {
                $theme = $service->registerDefinition($context, [
                    'module_key' => self::MODULE_KEY,
                    'theme_key' => self::THEME_KEY,
                    'name' => 'Alpha Default Theme',
                    'tokens' => ['color.primary' => '#2563eb', 'color.surface' => '#ffffff'],
                ]);
            }

            $service->updateBrandProfile($context, $theme->publicId, [
                'name' => 'Moondew Alpha Brand',
                'colors' => ['primary' => '#2563eb', 'accent' => '#0f172a'],
            ]);

            if ($theme->status !== 'published') {
                $service->publishDefinition($context, $theme->publicId);
            }
        });
    }

    private function ensurePersonalization(TenantContext $context): void
    {
        if (! Schema::hasTable('personalization_profiles')) {
            $this->warnSection('personalization', 'personalization tables missing — run migrations first.');

            return;
        }

        $this->runSection('personalization', function () use ($context) {
            app(PersonalizationRegistryService::class)->ensureProfile($context);
            app(PreferenceService::class)->upsert($context, 'locale', 'string', 'en');
            app(PreferenceService::class)->upsert($context, 'alpha.demo.ready', 'boolean', true);
        });
    }

    private function ensureNavigation(TenantContext $context): void
    {
        if (! Schema::hasTable('navigation_definitions')) {
            $this->warnSection('navigation', 'navigation_definitions table missing — run migrations first.');

            return;
        }

        $this->runSection('navigation', function () use ($context) {
            $service = app(NavigationDevelopmentService::class);
            $definition = collect($service->listDefinitions($context))->first(
                fn ($item) => $item->moduleKey === self::MODULE_KEY && $item->navigationKey === self::NAVIGATION_KEY,
            );

            if ($definition === null) {
                $definition = $service->registerDefinition($context, [
                    'module_key' => self::MODULE_KEY,
                    'navigation_key' => self::NAVIGATION_KEY,
                    'name' => 'Alpha Primary Navigation',
                    'navigation_type' => 'primary',
                ]);
            }

            $existingKeys = collect($service->listItems($context, $definition->publicId))
                ->map(fn ($item) => $item->itemKey)
                ->all();

            foreach ($this->alphaNavigationItems() as $item) {
                if (in_array($item['item_key'], $existingKeys, true)) {
                    continue;
                }

                $service->createItemForDefinition($context, $definition->publicId, $item);
            }

            if ($definition->status !== 'published') {
                $service->createVersion($context, $definition->publicId, ['items' => []]);
                $service->publishDefinition($context, $definition->publicId);
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function alphaNavigationItems(): array
    {
        $module = self::MODULE_KEY;

        return [
            [
                'item_key' => 'alpha-home',
                'label' => 'Alpha Preview Home',
                'item_type' => 'route',
                'module_key' => $module,
                'route' => '/app/'.$module.'/'.self::PAGE_KEY,
                'sort_order' => 10,
                'metadata' => ['page_key' => self::PAGE_KEY, 'icon' => 'home', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-form',
                'label' => 'Sample Form',
                'item_type' => 'route',
                'module_key' => $module,
                'route' => '/forms/'.$module.'/'.self::FORM_KEY,
                'sort_order' => 20,
                'metadata' => ['icon' => 'form', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-table',
                'label' => 'Sample Table',
                'item_type' => 'route',
                'module_key' => $module,
                'route' => '/tables/'.$module.'/'.self::TABLE_KEY,
                'sort_order' => 30,
                'metadata' => ['icon' => 'table', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-dashboard',
                'label' => 'Sample Dashboard',
                'item_type' => 'route',
                'module_key' => $module,
                'route' => '/dashboards/'.$module.'/'.self::DASHBOARD_KEY,
                'sort_order' => 40,
                'metadata' => ['icon' => 'dashboard', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-report',
                'label' => 'Sample Report',
                'item_type' => 'route',
                'module_key' => $module,
                'route' => '/reports/'.$module.'/'.self::REPORT_KEY,
                'sort_order' => 50,
                'metadata' => ['icon' => 'report', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-documents',
                'label' => 'Documents',
                'item_type' => 'route',
                'route' => '/documents',
                'sort_order' => 60,
                'metadata' => ['icon' => 'document', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-workflows',
                'label' => 'Workflows',
                'item_type' => 'route',
                'route' => '/workflows',
                'sort_order' => 70,
                'metadata' => ['icon' => 'workflow', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-notifications',
                'label' => 'Notifications',
                'item_type' => 'route',
                'route' => '/notifications',
                'sort_order' => 80,
                'metadata' => ['icon' => 'notification', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-activity',
                'label' => 'Activity',
                'item_type' => 'route',
                'route' => '/activity',
                'sort_order' => 90,
                'metadata' => ['icon' => 'activity', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-admin',
                'label' => 'Administration',
                'item_type' => 'route',
                'route' => '/admin',
                'sort_order' => 100,
                'metadata' => ['icon' => 'admin', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-health',
                'label' => 'Alpha Health',
                'item_type' => 'route',
                'route' => '/alpha/health',
                'sort_order' => 110,
                'metadata' => ['icon' => 'health', 'group_key' => 'default'],
            ],
            [
                'item_key' => 'alpha-search',
                'label' => 'Search',
                'item_type' => 'route',
                'route' => '/search',
                'sort_order' => 120,
                'metadata' => ['icon' => 'search', 'group_key' => 'default'],
            ],
        ];
    }

    private function ensureUiMetadata(TenantContext $context): void
    {
        if (! Schema::hasTable('ui_pages')) {
            $this->warnSection('ui', 'ui_pages table missing — run migrations first.');

            return;
        }

        $this->runSection('ui metadata', function () use ($context) {
            $service = app(UiDevelopmentService::class);
            $existing = UiPage::query()
                ->where('organization_id', $context->organization->id)
                ->where('workspace_id', $context->workspace?->id)
                ->where('module_key', self::MODULE_KEY)
                ->where('page_key', self::PAGE_KEY)
                ->exists();

            if ($existing) {
                return;
            }

            $service->registerPage($context, [
                'module_key' => self::MODULE_KEY,
                'page_key' => self::PAGE_KEY,
                'name' => 'Alpha Preview Home',
                'page_type' => 'module_home',
                'route_path' => '/app/'.self::MODULE_KEY.'/'.self::PAGE_KEY,
                'layout' => ['layout_type' => 'single_column'],
                'components' => [[
                    'component_key' => 'platform_overview',
                    'name' => 'Platform Overview',
                    'component_type' => 'platform_overview',
                    'binding_type' => 'custom',
                    'binding_config' => ['mode' => 'platform_overview'],
                ]],
            ]);
        });
    }

    private function ensureFormDefinition(TenantContext $context): void
    {
        $this->registerArtifact($context, 'form', DynamicFormDevelopmentService::class, self::FORM_KEY, [
            'module_key' => self::MODULE_KEY,
            'form_key' => self::FORM_KEY,
            'name' => 'Alpha Sample Form',
            'description' => 'Alpha validation sample form.',
            'type' => 'create',
            'status' => 'registered',
            'visibility' => 'organization',
            'fields' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
            ]],
            'metadata' => ['owner' => 'alpha.demo'],
        ]);
    }

    private function ensureTableDefinition(TenantContext $context): void
    {
        $this->registerArtifact($context, 'table', DynamicTableDevelopmentService::class, self::TABLE_KEY, [
            'module_key' => self::MODULE_KEY,
            'table_key' => self::TABLE_KEY,
            'name' => 'Alpha Sample Table',
            'description' => 'Alpha validation sample table.',
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
            'metadata' => ['owner' => 'alpha.demo'],
        ]);
    }

    private function ensureDashboardDefinition(TenantContext $context): void
    {
        $this->registerArtifact($context, 'dashboard', DynamicDashboardDevelopmentService::class, self::DASHBOARD_KEY, [
            'module_key' => self::MODULE_KEY,
            'dashboard_key' => self::DASHBOARD_KEY,
            'name' => 'Alpha Sample Dashboard',
            'description' => 'Alpha validation sample dashboard.',
            'type' => 'entity',
            'status' => 'registered',
            'visibility' => 'organization',
            'widgets' => [[
                'widget_key' => 'alpha_metric',
                'name' => 'Alpha Metric',
                'widget_type' => 'kpi_card',
                'data_source_type' => 'entity_count',
            ]],
            'metadata' => ['owner' => 'alpha.demo'],
        ]);
    }

    private function ensureReportDefinition(TenantContext $context): void
    {
        $this->registerArtifact($context, 'report', DynamicReportDevelopmentService::class, self::REPORT_KEY, [
            'module_key' => self::MODULE_KEY,
            'report_key' => self::REPORT_KEY,
            'name' => 'Alpha Sample Report',
            'description' => 'Alpha validation sample report.',
            'type' => 'tabular',
            'status' => 'registered',
            'visibility' => 'organization',
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'string',
            ]],
            'metadata' => ['owner' => 'alpha.demo'],
        ]);
    }

    /**
     * @param  class-string  $serviceClass
     * @param  array<string, mixed>  $payload
     */
    private function registerArtifact(
        TenantContext $context,
        string $label,
        string $serviceClass,
        string $artifactKey,
        array $payload,
    ): void {
        $this->runSection($label, function () use ($context, $serviceClass, $artifactKey, $payload) {
            $service = app($serviceClass);
            try {
                $service->findDefinition($context, self::MODULE_KEY, $artifactKey);
            } catch (\Throwable) {
                $service->registerDefinition($context, $payload);
            }
        });
    }

    private function ensureDocumentSample(TenantContext $context): void
    {
        if (! Schema::hasTable('enterprise_documents')) {
            return;
        }

        $this->runSection('document', function () use ($context) {
            $service = app(EnterpriseDocumentDevelopmentService::class);
            $existing = collect($service->list($context, 25))->first(
                fn ($document) => ($document->metadata['alpha_demo'] ?? false) === true,
            );

            if ($existing !== null) {
                return;
            }

            $service->createPlaceholder(
                $context,
                'Alpha Validation Document',
                self::MODULE_KEY,
                ['alpha_demo' => true, 'source' => 'alpha_demo_seeder'],
            );
        });
    }

    private function ensureNotificationSample(TenantContext $context): void
    {
        if (! Schema::hasTable('enterprise_notifications')) {
            return;
        }

        $this->runSection('notification', function () use ($context) {
            $service = app(NotificationDevelopmentService::class);
            $existing = collect($service->list($context, 25))->first(
                fn ($notification) => ($notification->metadata['alpha_demo'] ?? false) === true,
            );

            if ($existing !== null) {
                return;
            }

            $service->send($context, NotificationMessage::fromArray([
                'title' => 'Alpha validation notification',
                'body' => 'Hosteady Platform Preview sample notification for Moondew Group.',
                'scope' => 'user',
                'priority' => 'normal',
                'module_key' => self::MODULE_KEY,
                'channels' => ['in_app'],
                'recipient_membership_public_id' => $context->membershipPublicId,
                'merge_data' => [],
                'metadata' => ['alpha_demo' => true, 'type' => 'alpha.validation'],
            ]));
        });
    }

    private function ensureAuditSample(TenantContext $context): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $count = AuditLog::query()
            ->where('organization_id', $context->organization->id)
            ->count();

        if ($count > 0) {
            $this->command?->info("Alpha demo [audit] OK ({$count} audit events)");

            return;
        }

        $this->warnSection('audit', 'No audit events found for Moondew Group.');
    }

    private function ensureWorkflowSample(TenantContext $context): void
    {
        // Workflow definitions require designer contracts; documented as manual gap for Alpha.
        $this->command?->line('Workflow sample: manual provisioning documented in HEOS_ALPHA_PROVISIONING_PLAN.md.');
    }

    private function runSection(string $section, callable $callback): void
    {
        try {
            $callback();
            $this->command?->info("Alpha demo [{$section}] OK");
        } catch (\Throwable $exception) {
            $this->warnSection($section, $exception->getMessage());
        }
    }

    private function warnSection(string $section, string $message): void
    {
        $this->command?->warn("Alpha demo [{$section}] skipped: {$message}");
    }
}
