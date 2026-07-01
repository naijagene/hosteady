<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Exceptions\Application\ApplicationAlreadyInstalledException;
use App\Exceptions\WorkspaceApplication\DuplicateWorkspaceApplicationException;
use App\Modules\Sdk\Application\Exceptions\ApplicationRegistrationException;
use App\Models\Application;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Models\AuditLog;
use App\Models\NavigationDefinition;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UiPage;
use App\Models\User;
use App\Models\Workspace;
use App\Modules\HosteadyAdmin\HosteadyAdminModule;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Application\ApplicationRuntimeRegistryService;
use App\Services\Audit\Data\AuditEventData;
use App\Services\Audit\DomainAuditRecorder;
use App\Services\Dashboard\DynamicDashboardDevelopmentService;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Form\DynamicFormDevelopmentService;
use App\Services\Navigation\NavigationDevelopmentService;
use App\Services\Notification\NotificationDevelopmentService;
use App\Services\Report\DynamicReportDevelopmentService;
use App\Services\Table\DynamicTableDevelopmentService;
use App\Services\Ui\UiDevelopmentService;
use App\Services\Ui\UiMapper;
use App\Services\Ui\UiPageDefinitionService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HosteadyAdminApplicationSeeder extends Seeder
{
    public const APPLICATION_KEY = 'hosteady.admin';

    public const MODULE_KEY = 'hosteady.admin';

    public const CATALOG_MODULE_KEY = 'hosteady-admin';

    public const NAVIGATION_KEY = 'admin-primary';

    public const NAVIGATION_GROUP_KEY = 'hosteady-admin';

    public const NAVIGATION_GROUP_LABEL = 'Hosteady Admin';

    public const DASHBOARD_KEY = 'overview';

    /**
     * @var list<string>
     */
    public const PERMISSION_KEYS = [
        'hosteady.admin.read',
        'hosteady.admin.manage',
        'hosteady.admin.configure',
        'hosteady.admin.reports.read',
        'hosteady.admin.audit.read',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'owner' => self::PERMISSION_KEYS,
        'administrator' => self::PERMISSION_KEYS,
        'manager' => [
            'hosteady.admin.read',
            'hosteady.admin.reports.read',
            'hosteady.admin.audit.read',
        ],
        'viewer' => [
            'hosteady.admin.read',
        ],
    ];

    public function run(): void
    {
        $this->ensurePlatformBootstrap();

        $organization = Organization::query()
            ->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)
            ->whereNull('deleted_at')
            ->first();

        if ($organization === null) {
            $this->command?->error('Moondew Group not found — run AlphaDemoSeeder first.');
            $this->command?->line('Example: php artisan db:seed --class=AlphaDemoSeeder');

            return;
        }

        $workspace = $organization->workspaces()
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->whereNull('deleted_at')
            ->first();

        if ($workspace === null) {
            $this->command?->error('Production workspace not found — run AlphaDemoSeeder first.');

            return;
        }

        $adminUser = User::query()->where('email', AlphaDemoSeeder::DEMO_USERS['admin']['email'])->first();

        if ($adminUser === null) {
            $this->command?->error('Alpha administrator user not found — run AlphaDemoSeeder first.');

            return;
        }

        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $adminUser->id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $context = TenantContext::fromModels($adminUser, $organization, $membership, $workspace);
        app()->instance(TenantContext::class, $context);

        $catalogApplication = $this->ensureApplicationRegistered();

        if ($catalogApplication === null) {
            return;
        }

        $this->ensureApplicationInstalled($context, $catalogApplication);
        $this->ensureEnterpriseRuntimeApplication($context, $catalogApplication);
        $this->ensureRolePermissions($organization, $adminUser->id);
        $this->ensureNavigation($context);
        $this->ensureUiPages($context);
        $this->ensureForms($context);
        $this->ensureTables($context);
        $this->ensureDashboard($context);
        $this->ensureReports($context);
        $this->ensureNotificationSample($context);
        $this->ensureAuditSample($context, $adminUser->id);
        $this->ensureSearchIndex($context);

        $this->command?->info('Hosteady Admin application seed complete.');
        $this->command?->line('Application: '.self::APPLICATION_KEY);
        $this->command?->line('Organization: '.AlphaDemoSeeder::ORGANIZATION_NAME.' ('.$organization->public_id.')');
        $this->command?->line('Workspace: '.AlphaDemoSeeder::WORKSPACE_NAME.' ('.$workspace->public_id.')');
    }

    private function ensurePlatformBootstrap(): void
    {
        if (! Permission::query()->where('key', 'hosteady.admin.read')->exists()) {
            $this->call(PermissionCatalogSeeder::class);
        }

        $catalog = Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->first();

        if ($catalog === null) {
            Artisan::call('heos:sync-modules');
        }
    }

    private function ensureApplicationRegistered(): ?Application
    {
        $application = Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->first();

        if ($application === null) {
            Artisan::call('heos:sync-modules');

            $application = Application::query()
                ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
                ->first();
        }

        if ($application === null) {
            $this->command?->error('Hosteady Admin catalog entry missing — verify module sync is enabled.');

            return null;
        }

        if ($application->key !== self::CATALOG_MODULE_KEY) {
            $application->key = self::CATALOG_MODULE_KEY;
        }

        $application->fill([
            'name' => 'Hosteady Admin',
            'description' => 'Reference administration application for HEOS platform operations.',
            'version' => '0.1.0-alpha',
            'category' => 'platform',
            'status' => ApplicationStatus::Active,
        ]);
        $application->save();

        $this->cleanupLegacyCatalogDuplicate($application);

        $this->command?->info('Hosteady Admin [catalog] OK');

        return $application->fresh();
    }

    /**
     * Remove legacy catalog rows created when the seeder renamed the synced module key to hosteady.admin.
     */
    private function cleanupLegacyCatalogDuplicate(Application $canonical): void
    {
        $duplicates = Application::query()
            ->where('key', self::APPLICATION_KEY)
            ->where('id', '!=', $canonical->id)
            ->get();

        foreach ($duplicates as $duplicate) {
            OrganizationApplication::query()
                ->where('application_id', $duplicate->id)
                ->update(['application_id' => $canonical->id]);

            $duplicate->delete();
        }
    }

    private function ensureApplicationInstalled(TenantContext $context, Application $application): void
    {
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

        $this->command?->info('Hosteady Admin [install] OK');
    }

    private function ensureEnterpriseRuntimeApplication(TenantContext $context, Application $catalogApplication): void
    {
        $this->cleanupStaleRuntimeApplications($context);

        $existing = ApplicationRuntimeApp::query()
            ->where('organization_id', $context->organization->id)
            ->where('application_key', self::MODULE_KEY)
            ->first();

        if ($existing !== null) {
            if ($existing->name === 'Application' || trim((string) $existing->name) === '') {
                $existing->name = 'Hosteady Admin';
                $existing->save();
            }

            return;
        }

        try {
            app(ApplicationRuntimeRegistryService::class)->register(
                $context->organization->id,
                $context->workspace?->id,
                ApplicationDefinition::fromArray([
                    'application_key' => self::MODULE_KEY,
                    'name' => 'Hosteady Admin',
                    'description' => 'Reference administration application for HEOS platform operations.',
                    'application_type' => 'platform',
                    'visibility' => 'workspace',
                    'module_key' => self::MODULE_KEY,
                    'metadata' => [
                        'catalog_application_key' => self::CATALOG_MODULE_KEY,
                        'catalog_module_uuid' => $catalogApplication->module_uuid,
                        'catalog_application_id' => $catalogApplication->public_id,
                    ],
                ]),
            );
        } catch (ApplicationRegistrationException) {
            // Idempotent when another process registered the runtime application first.
        }
    }

    private function cleanupStaleRuntimeApplications(TenantContext $context): void
    {
        ApplicationRuntimeApp::query()
            ->where('organization_id', $context->organization->id)
            ->where('module_key', self::MODULE_KEY)
            ->get()
            ->filter(function (ApplicationRuntimeApp $app): bool {
                $name = trim((string) $app->name);

                return ($name === '' || $name === 'Application')
                    && $this->isUuidLikeKey($app->application_key);
            })
            ->each
            ->delete();
    }

    private function isUuidLikeKey(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        );
    }

    private function ensureRolePermissions(Organization $organization, int $actorUserId): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('key', self::PERMISSION_KEYS)
            ->get()
            ->keyBy('key');

        if ($permissions->count() !== count(self::PERMISSION_KEYS)) {
            $this->warnSection('permissions', 'Hosteady Admin permission catalog is incomplete — run PermissionCatalogSeeder.');

            return;
        }

        foreach (self::ROLE_PERMISSIONS as $roleKey => $permissionKeys) {
            $role = Role::query()
                ->where('organization_id', $organization->id)
                ->where('key', $roleKey)
                ->where('is_system', true)
                ->whereNull('deleted_at')
                ->first();

            if ($role === null) {
                continue;
            }

            foreach ($permissionKeys as $permissionKey) {
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

        $this->command?->info('Hosteady Admin [permissions] OK');
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
                    'name' => self::NAVIGATION_GROUP_LABEL,
                    'navigation_type' => 'primary',
                    'metadata' => ['application_key' => self::APPLICATION_KEY],
                ]);
            }

            $existingKeys = collect($service->listItems($context, $definition->publicId))
                ->map(fn ($item) => $item->itemKey)
                ->all();

            foreach ($this->navigationItems() as $item) {
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
    private function navigationItems(): array
    {
        $module = self::MODULE_KEY;
        $group = self::NAVIGATION_GROUP_KEY;

        return [
            $this->navItem('hosteady-overview', 'Overview', '/app/'.$module.'/overview', 10, 'overview', $group),
            $this->navItem('hosteady-organizations', 'Organizations', '/app/'.$module.'/organizations', 20, 'organizations', $group),
            $this->navItem('hosteady-workspaces', 'Workspaces', '/app/'.$module.'/workspaces', 30, 'workspaces', $group),
            $this->navItem('hosteady-applications', 'Applications', '/app/'.$module.'/applications', 40, 'applications', $group),
            $this->navItem('hosteady-users', 'Users', '/app/'.$module.'/users', 50, 'users', $group),
            $this->navItem('hosteady-roles-permissions', 'Roles & Permissions', '/app/'.$module.'/roles-permissions', 60, 'roles-permissions', $group),
            $this->navItem('hosteady-runtime', 'Runtime Diagnostics', '/app/'.$module.'/runtime', 70, 'runtime', $group),
            $this->navItem('hosteady-activity-audit', 'Activity & Audit', '/app/'.$module.'/activity-audit', 80, 'activity-audit', $group),
            $this->navItem('hosteady-reports', 'Reports', '/app/'.$module.'/reports', 90, 'reports', $group),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function navItem(
        string $itemKey,
        string $label,
        string $route,
        int $sortOrder,
        string $pageKey,
        string $groupKey,
    ): array {
        return [
            'item_key' => $itemKey,
            'label' => $label,
            'item_type' => 'route',
            'module_key' => self::MODULE_KEY,
            'route' => $route,
            'sort_order' => $sortOrder,
            'metadata' => [
                'page_key' => $pageKey,
                'group_key' => $groupKey,
                'group_label' => self::NAVIGATION_GROUP_LABEL,
                'application_key' => self::APPLICATION_KEY,
            ],
        ];
    }

    private function ensureUiPages(TenantContext $context): void
    {
        if (! Schema::hasTable('ui_pages')) {
            $this->warnSection('ui', 'ui_pages table missing — run migrations first.');

            return;
        }

        $this->runSection('ui pages', function () use ($context) {
            $service = app(UiDevelopmentService::class);
            $pageService = app(UiPageDefinitionService::class);

            foreach ($this->pageDefinitions() as $page) {
                $existing = UiPage::query()
                    ->where('organization_id', $context->organization->id)
                    ->where('workspace_id', $context->workspace?->id)
                    ->where('module_key', self::MODULE_KEY)
                    ->where('page_key', $page['page_key'])
                    ->first();

                if ($existing === null) {
                    $service->registerPage($context, $page);

                    continue;
                }

                if (! $this->pageNeedsLayoutRepair($existing)) {
                    continue;
                }

                $definition = UiMapper::toPageDefinition($existing);
                $updated = UiPageDefinition::fromArray(array_merge($definition->toArray(), [
                    'layout' => $page['layout'],
                    'regions' => $page['regions'],
                    'components' => $page['components'],
                    'status' => 'published',
                ]));

                $pageService->update($context, $updated);
            }
        });
    }

    private function pageNeedsLayoutRepair(UiPage $page): bool
    {
        $regions = is_array($page->regions_json) ? $page->regions_json : [];

        return $regions === [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pageDefinitions(): array
    {
        return [
            $this->metadataPage(
                pageKey: 'overview',
                name: 'Overview',
                pageType: 'module_home',
                layoutType: 'single_column',
                components: [
                    $this->adminBinding('overview_platform', 'Platform Status', 'platform_overview', ['mode' => 'platform_overview']),
                    $this->dashboardBinding('overview_dashboard', 'Overview Dashboard', self::DASHBOARD_KEY),
                ],
            ),
            $this->metadataPage(
                pageKey: 'organizations',
                name: 'Organizations',
                layoutType: 'single_column',
                components: [
                    $this->adminBinding('organizations_summary', 'Organization Summary', 'organization_summary', ['mode' => 'organization_summary']),
                    $this->tableBinding('organizations_table', 'Organizations Table', 'organizations'),
                ],
            ),
            $this->metadataPage(
                pageKey: 'workspaces',
                name: 'Workspaces',
                layoutType: 'single_column',
                components: [
                    $this->tableBinding('workspaces_table', 'Workspaces Table', 'workspaces'),
                ],
            ),
            $this->metadataPage(
                pageKey: 'applications',
                name: 'Applications',
                layoutType: 'single_column',
                components: [
                    $this->tableBinding('applications_table', 'Applications Table', 'applications'),
                ],
            ),
            $this->metadataPage(
                pageKey: 'users',
                name: 'Users',
                layoutType: 'single_column',
                components: [
                    $this->tableBinding('users_table', 'Users Table', 'users'),
                ],
            ),
            $this->metadataPage(
                pageKey: 'roles-permissions',
                name: 'Roles & Permissions',
                layoutType: 'two_column',
                components: [
                    $this->adminBinding('roles_browser', 'Roles', 'role_browser', ['mode' => 'role_browser']),
                    $this->adminBinding('permissions_browser', 'Permissions', 'permission_browser', ['mode' => 'permission_browser']),
                ],
                regions: [
                    [
                        'region_key' => 'primary',
                        'region_type' => 'content',
                        'label' => 'Roles',
                        'sort_order' => 10,
                        'components' => ['roles_browser'],
                    ],
                    [
                        'region_key' => 'secondary',
                        'region_type' => 'content',
                        'label' => 'Permissions',
                        'sort_order' => 20,
                        'components' => ['permissions_browser'],
                    ],
                ],
            ),
            $this->metadataPage(
                pageKey: 'runtime',
                name: 'Runtime Diagnostics',
                layoutType: 'single_column',
                components: [
                    $this->adminBinding('runtime_diagnostics', 'Runtime Diagnostics', 'runtime_summary', ['mode' => 'runtime_summary']),
                ],
            ),
            $this->metadataPage(
                pageKey: 'activity-audit',
                name: 'Activity & Audit',
                layoutType: 'single_column',
                components: [
                    $this->activityBinding('activity_feed', 'Activity Feed'),
                    $this->tableBinding('activity_table', 'Activity Table', 'activity'),
                ],
            ),
            $this->metadataPage(
                pageKey: 'reports',
                name: 'Reports',
                layoutType: 'single_column',
                components: [
                    $this->reportBinding('platform_health_report', 'Platform Health Report', 'platform-health'),
                    $this->reportBinding('permission_coverage_report', 'Permission Coverage Report', 'permission-coverage'),
                    $this->reportBinding('activity_summary_report', 'Activity Summary Report', 'activity-summary'),
                ],
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @param  list<array<string, mixed>>|null  $regions
     * @return array<string, mixed>
     */
    private function metadataPage(
        string $pageKey,
        string $name,
        string $layoutType,
        array $components,
        string $pageType = 'module_page',
        ?array $regions = null,
    ): array {
        $module = self::MODULE_KEY;

        if ($regions === null) {
            $regions = [[
                'region_key' => 'main',
                'region_type' => 'content',
                'label' => 'Main',
                'sort_order' => 10,
                'components' => array_column($components, 'component_key'),
            ]];
        }

        $componentRegionMap = [];

        foreach ($regions as $region) {
            foreach ($region['components'] as $componentKey) {
                $componentRegionMap[$componentKey] = $region['region_key'];
            }
        }

        $componentsWithRegions = [];

        foreach ($components as $index => $component) {
            $componentKey = $component['component_key'];
            $componentsWithRegions[] = array_merge($component, [
                'region_key' => $componentRegionMap[$componentKey] ?? ($regions[0]['region_key'] ?? 'main'),
                'sort_order' => $component['sort_order'] ?? (($index + 1) * 10),
            ]);
        }

        $normalizedRegions = [];

        foreach ($regions as $index => $region) {
            $normalizedRegions[] = [
                'region_key' => $region['region_key'],
                'region_type' => $region['region_type'] ?? 'content',
                'label' => $region['label'] ?? ucfirst(str_replace('_', ' ', $region['region_key'])),
                'sort_order' => $region['sort_order'] ?? (($index + 1) * 10),
                'components' => $region['components'],
            ];
        }

        return [
            'module_key' => $module,
            'page_key' => $pageKey,
            'name' => $name,
            'page_type' => $pageType,
            'status' => 'published',
            'visibility' => 'organization',
            'route_path' => '/app/'.$module.'/'.$pageKey,
            'layout' => ['layout_type' => $layoutType],
            'regions' => $normalizedRegions,
            'components' => $componentsWithRegions,
            'metadata' => ['owner' => self::APPLICATION_KEY],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function adminBinding(string $componentKey, string $name, string $componentType, array $config): array
    {
        return [
            'component_key' => $componentKey,
            'name' => $name,
            'component_type' => $componentType,
            'binding_type' => $componentType,
            'binding_config' => $config,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableBinding(string $componentKey, string $name, string $tableKey): array
    {
        return [
            'component_key' => $componentKey,
            'name' => $name,
            'component_type' => 'table',
            'binding_type' => 'table',
            'binding_config' => [
                'module_key' => self::MODULE_KEY,
                'table_key' => $tableKey,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardBinding(string $componentKey, string $name, string $dashboardKey): array
    {
        return [
            'component_key' => $componentKey,
            'name' => $name,
            'component_type' => 'dashboard',
            'binding_type' => 'dashboard',
            'binding_config' => [
                'module_key' => self::MODULE_KEY,
                'dashboard_key' => $dashboardKey,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportBinding(string $componentKey, string $name, string $reportKey): array
    {
        return [
            'component_key' => $componentKey,
            'name' => $name,
            'component_type' => 'report',
            'binding_type' => 'report',
            'binding_config' => [
                'module_key' => self::MODULE_KEY,
                'report_key' => $reportKey,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activityBinding(string $componentKey, string $name): array
    {
        return [
            'component_key' => $componentKey,
            'name' => $name,
            'component_type' => 'activity_feed',
            'binding_type' => 'activity_feed',
            'binding_config' => ['mode' => 'recent'],
        ];
    }

    private function ensureForms(TenantContext $context): void
    {
        $forms = [
            'organization-profile' => [
                'name' => 'Organization Profile Form',
                'fields' => [
                    ['key' => 'name', 'label' => 'Organization Name', 'type' => 'string', 'required' => true],
                    ['key' => 'slug', 'label' => 'Slug', 'type' => 'string', 'required' => false, 'readonly' => true],
                ],
            ],
            'workspace-profile' => [
                'name' => 'Workspace Profile Form',
                'fields' => [
                    ['key' => 'name', 'label' => 'Workspace Name', 'type' => 'string', 'required' => true],
                    ['key' => 'slug', 'label' => 'Slug', 'type' => 'string', 'required' => false, 'readonly' => true],
                ],
            ],
            'user-invite' => [
                'name' => 'User Invite Form',
                'fields' => [
                    ['key' => 'email', 'label' => 'Email', 'type' => 'string', 'required' => true],
                    ['key' => 'role_key', 'label' => 'Role', 'type' => 'string', 'required' => true],
                ],
            ],
            'application-settings' => [
                'name' => 'Application Settings Form',
                'fields' => [
                    ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'boolean', 'required' => false],
                    ['key' => 'notes', 'label' => 'Notes', 'type' => 'text', 'required' => false],
                ],
            ],
        ];

        foreach ($forms as $formKey => $definition) {
            $this->registerArtifact($context, 'form', DynamicFormDevelopmentService::class, $formKey, [
                'module_key' => self::MODULE_KEY,
                'form_key' => $formKey,
                'name' => $definition['name'],
                'description' => 'Hosteady Admin '.$definition['name'].'.',
                'type' => 'create',
                'status' => 'registered',
                'visibility' => 'organization',
                'fields' => $definition['fields'],
                'metadata' => ['owner' => 'hosteady.admin', 'placeholder' => true],
            ]);
        }
    }

    private function ensureTables(TenantContext $context): void
    {
        $tables = [
            'organizations' => ['name' => 'Organizations Table', 'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'sortable' => true, 'filterable' => true, 'searchable' => true],
                ['key' => 'slug', 'label' => 'Slug', 'type' => 'text', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'sortable' => true],
            ]],
            'workspaces' => ['name' => 'Workspaces Table', 'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'sortable' => true, 'filterable' => true],
                ['key' => 'slug', 'label' => 'Slug', 'type' => 'text', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'sortable' => true],
            ]],
            'applications' => ['name' => 'Applications Table', 'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'sortable' => true, 'searchable' => true],
                ['key' => 'key', 'label' => 'Key', 'type' => 'text', 'sortable' => true],
                ['key' => 'version', 'label' => 'Version', 'type' => 'text'],
            ]],
            'users' => ['name' => 'Users Table', 'columns' => [
                ['key' => 'display_name', 'label' => 'Name', 'type' => 'text', 'sortable' => true, 'searchable' => true],
                ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
            ]],
            'permissions' => ['name' => 'Permissions Table', 'columns' => [
                ['key' => 'key', 'label' => 'Key', 'type' => 'text', 'sortable' => true, 'searchable' => true],
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'sortable' => true],
                ['key' => 'domain', 'label' => 'Domain', 'type' => 'text'],
            ]],
            'activity' => ['name' => 'Activity Table', 'columns' => [
                ['key' => 'action', 'label' => 'Action', 'type' => 'text', 'sortable' => true],
                ['key' => 'summary', 'label' => 'Summary', 'type' => 'text', 'searchable' => true],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime', 'sortable' => true],
            ]],
        ];

        foreach ($tables as $tableKey => $definition) {
            $this->registerArtifact($context, 'table', DynamicTableDevelopmentService::class, $tableKey, [
                'module_key' => self::MODULE_KEY,
                'table_key' => $tableKey,
                'name' => $definition['name'],
                'description' => 'Hosteady Admin '.$definition['name'].' (placeholder metadata).',
                'type' => 'list',
                'status' => 'registered',
                'visibility' => 'organization',
                'columns' => $definition['columns'],
                'pagination' => ['page' => 1, 'per_page' => 25],
                'metadata' => ['owner' => 'hosteady.admin', 'placeholder' => true, 'empty_state' => true],
            ]);
        }
    }

    private function ensureDashboard(TenantContext $context): void
    {
        $this->registerArtifact($context, 'dashboard', DynamicDashboardDevelopmentService::class, self::DASHBOARD_KEY, [
            'module_key' => self::MODULE_KEY,
            'dashboard_key' => self::DASHBOARD_KEY,
            'name' => 'Hosteady Admin Overview',
            'description' => 'Overview dashboard for Hosteady Admin.',
            'type' => 'entity',
            'status' => 'registered',
            'visibility' => 'organization',
            'widgets' => [
                ['widget_key' => 'platform_status', 'name' => 'Platform Status', 'widget_type' => 'kpi_card', 'data_source_type' => 'static', 'metadata' => ['value' => 'Alpha Ready']],
                ['widget_key' => 'runtime_status', 'name' => 'Runtime Status', 'widget_type' => 'kpi_card', 'data_source_type' => 'static', 'metadata' => ['value' => 'Hydrated']],
                ['widget_key' => 'organization_summary', 'name' => 'Organization Summary', 'widget_type' => 'kpi_card', 'data_source_type' => 'entity_count'],
                ['widget_key' => 'workspace_summary', 'name' => 'Workspace Summary', 'widget_type' => 'kpi_card', 'data_source_type' => 'entity_count'],
                ['widget_key' => 'application_count', 'name' => 'Application Count', 'widget_type' => 'kpi_card', 'data_source_type' => 'entity_count'],
                ['widget_key' => 'user_count', 'name' => 'User Count', 'widget_type' => 'kpi_card', 'data_source_type' => 'static', 'metadata' => ['value' => 'Placeholder']],
                ['widget_key' => 'notification_summary', 'name' => 'Notification Summary', 'widget_type' => 'kpi_card', 'data_source_type' => 'static'],
                ['widget_key' => 'activity_summary', 'name' => 'Activity Summary', 'widget_type' => 'kpi_card', 'data_source_type' => 'static'],
                ['widget_key' => 'recent_audit_events', 'name' => 'Recent Audit Events', 'widget_type' => 'list', 'data_source_type' => 'audit_recent'],
            ],
            'metadata' => ['owner' => 'hosteady.admin'],
        ]);
    }

    private function ensureReports(TenantContext $context): void
    {
        $reports = [
            'platform-health' => 'Platform Health Report',
            'permission-coverage' => 'Permission Coverage Report',
            'activity-summary' => 'Activity Summary Report',
        ];

        foreach ($reports as $reportKey => $name) {
            $this->registerArtifact($context, 'report', DynamicReportDevelopmentService::class, $reportKey, [
                'module_key' => self::MODULE_KEY,
                'report_key' => $reportKey,
                'name' => $name,
                'description' => 'Hosteady Admin '.$name.'.',
                'type' => 'tabular',
                'status' => 'registered',
                'visibility' => 'organization',
                'columns' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'string'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'string'],
                    ['key' => 'detail', 'label' => 'Detail', 'type' => 'string'],
                ],
                'metadata' => ['owner' => 'hosteady.admin', 'placeholder' => true],
            ]);
        }
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
        $this->runSection($label.' '.$artifactKey, function () use ($context, $serviceClass, $artifactKey, $payload) {
            $service = app($serviceClass);
            try {
                $service->findDefinition($context, self::MODULE_KEY, $artifactKey);
            } catch (\Throwable) {
                $service->registerDefinition($context, $payload);
            }
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
                fn ($notification) => ($notification->metadata['hosteady_admin_installed'] ?? false) === true,
            );

            if ($existing !== null) {
                return;
            }

            $service->send($context, NotificationMessage::fromArray([
                'title' => 'Hosteady Admin installed',
                'body' => 'Hosteady Admin reference application is ready for Alpha validation.',
                'scope' => 'user',
                'priority' => 'normal',
                'module_key' => self::MODULE_KEY,
                'channels' => ['in_app'],
                'recipient_membership_public_id' => $context->membershipPublicId,
                'merge_data' => [],
                'metadata' => [
                    'hosteady_admin_installed' => true,
                    'category' => 'system',
                    'application_key' => self::APPLICATION_KEY,
                ],
            ]));
        });
    }

    private function ensureAuditSample(TenantContext $context, int $actorUserId): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $this->runSection('audit', function () use ($context, $actorUserId) {
            $exists = AuditLog::query()
                ->where('organization_id', $context->organization->id)
                ->where('metadata->hosteady_admin_installed', true)
                ->exists();

            if ($exists) {
                return;
            }

            $application = $this->resolveCatalogApplication();

            app(DomainAuditRecorder::class)->safeRecord(new AuditEventData(
                action: AuditAction::ApplicationInstalled,
                summary: 'Hosteady Admin reference application installed',
                scope: AuditScope::Organization,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                entityType: AuditEntityType::Application,
                entityPublicId: $application->public_id,
                entityLabel: $application->name,
                metadata: [
                    'hosteady_admin_installed' => true,
                    'custom_action' => 'hosteady_admin.installed',
                    'entity_key' => self::APPLICATION_KEY,
                ],
                actorType: AuditActorType::User,
                actorUserId: $actorUserId,
                actorMembershipId: $context->membership->id,
                retentionClass: AuditRetentionClass::Permanent,
                severity: AuditSeverity::Info,
            ));
        });
    }

    private function resolveCatalogApplication(): Application
    {
        return Application::query()
            ->where('module_uuid', HosteadyAdminModule::MODULE_UUID)
            ->firstOrFail();
    }

    private function ensureSearchIndex(TenantContext $context): void
    {
        if (! Schema::hasTable('platform_search_indexes')) {
            return;
        }

        $this->runSection('search index', function () use ($context) {
            $application = $this->resolveCatalogApplication();
            $searchService = app(SearchIndexService::class);
            $scope = new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: self::MODULE_KEY,
            );

            $entries = [
                [
                    'entity_type' => 'application',
                    'entity_public_id' => $application->public_id,
                    'display_name' => 'Hosteady Admin',
                    'keywords' => 'Hosteady Admin hosteady.admin platform administration',
                    'metadata' => ['application_key' => self::APPLICATION_KEY, 'route' => '/app/'.self::MODULE_KEY.'/overview'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-organizations',
                    'display_name' => 'Organizations',
                    'keywords' => 'Organizations hosteady admin organizations table',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/organizations'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-workspaces',
                    'display_name' => 'Workspaces',
                    'keywords' => 'Workspaces hosteady admin workspaces table',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/workspaces'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-applications',
                    'display_name' => 'Applications',
                    'keywords' => 'Applications hosteady admin applications registry',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/applications'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-users',
                    'display_name' => 'Users',
                    'keywords' => 'Users hosteady admin members directory',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/users'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-roles-permissions',
                    'display_name' => 'Permissions',
                    'keywords' => 'Permissions roles hosteady admin access control',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/roles-permissions'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-runtime',
                    'display_name' => 'Runtime Diagnostics',
                    'keywords' => 'Runtime Diagnostics hosteady admin runtime health',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/runtime'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-activity-audit',
                    'display_name' => 'Activity',
                    'keywords' => 'Activity audit hosteady admin audit trail',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/activity-audit'],
                ],
                [
                    'entity_type' => 'navigation_item',
                    'entity_public_id' => 'hosteady-reports',
                    'display_name' => 'Reports',
                    'keywords' => 'Reports hosteady admin platform health permission coverage activity summary',
                    'metadata' => ['route' => '/app/'.self::MODULE_KEY.'/reports'],
                ],
            ];

            foreach ($entries as $entry) {
                try {
                    $searchService->upsert($context, new SearchIndexUpsertRequest(
                        scope: $scope,
                        entityType: $entry['entity_type'],
                        entityPublicId: $entry['entity_public_id'],
                        displayName: $entry['display_name'],
                        keywords: $entry['keywords'],
                        metadata: $entry['metadata'],
                        entityReference: new EntityReference(
                            type: $entry['entity_type'],
                            publicId: $entry['entity_public_id'],
                            moduleKey: self::MODULE_KEY,
                            label: $entry['display_name'],
                        ),
                        visibility: 'organization',
                    ));
                } catch (\Throwable) {
                    // Search indexing is best-effort during seed.
                }
            }
        });
    }

    private function runSection(string $section, callable $callback): void
    {
        try {
            $callback();
            $this->command?->info("Hosteady Admin [{$section}] OK");
        } catch (\Throwable $exception) {
            $this->warnSection($section, $exception->getMessage());
        }
    }

    private function warnSection(string $section, string $message): void
    {
        $this->command?->warn("Hosteady Admin [{$section}] skipped: {$message}");
    }
}
