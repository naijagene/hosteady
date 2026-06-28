<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$testPath = $base.'/tests/Feature/Services/Application/M7ApplicationRuntimeTest.php';

$dtos = [
    'ApplicationDefinition', 'ApplicationManifest', 'ApplicationReference', 'ApplicationRuntimeMetadata',
    'ApplicationWorkspace', 'NavigationMenu', 'NavigationGroup', 'NavigationItem', 'NavigationBadge',
    'NavigationRoute', 'ApplicationStatistics', 'ApplicationHealthReport',
];

$enums = [
    ['ApplicationStatus', ['Registered', 'Enabled', 'Disabled', 'Archived']],
    ['NavigationItemType', ['Group', 'Item', 'Divider', 'Link', 'Module']],
    ['WorkspaceStatus', ['Active', 'Inactive', 'Archived']],
    ['ApplicationVisibility', ['Public', 'Private', 'Organization', 'Workspace']],
    ['ApplicationType', ['Core', 'Business', 'Custom', 'Module']],
];

$body = <<<'PHP'
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

PHP;

foreach ($enums as [$enum, $cases]) {
    $body .= "\n    public function test_{$enum}_enum_has_expected_cases(): void\n    {\n";
    $body .= "        \$cases = array_map(static fn (\\App\\Modules\\Sdk\\Application\\Enums\\{$enum} \$case) => \$case->value, \\App\\Modules\\Sdk\\Application\\Enums\\{$enum}::cases());\n";
    foreach ($cases as $case) {
        $val = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $case));
        $body .= "        \$this->assertContains('{$val}', \$cases);\n";
    }
    $body .= "    }\n";
}

foreach ($dtos as $dto) {
    $body .= "\n    public function test_{$dto}_dto_roundtrip(): void\n    {\n";
    $body .= "        \$sample = \\App\\Modules\\Sdk\\Application\\Data\\{$dto}::fromArray([]);\n";
    $body .= "        \$roundtrip = \\App\\Modules\\Sdk\\Application\\Data\\{$dto}::fromArray(\$sample->toArray());\n";
    $body .= "        \$this->assertSame(\$sample->toArray(), \$roundtrip->toArray());\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

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
        $this->assertSame(114, Permission::query()->count());
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
            ->getJson('/api/v1/tenant/applications')
            ->assertOk();
    }

    public function test_api_register_application(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/applications/register', [
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
            ->getJson('/api/v1/tenant/applications/'.$app->publicId)
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
            ->postJson('/api/v1/tenant/applications/'.$app->publicId.'/enable')
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Enabled->value);
    }

    public function test_api_navigation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/applications/navigation')
            ->assertOk();
    }

    public function test_api_workspaces(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/applications/workspaces')
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/applications');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_static_application_routes_resolve(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/applications/navigation')
            ->assertOk();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/applications/workspaces')
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

PHP;

file_put_contents($testPath, $body);
echo "Wrote {$testPath}\n";
echo 'Test methods: '.substr_count($body, 'public function test_')."\n";
