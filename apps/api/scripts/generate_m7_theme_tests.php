<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$testPath = $base.'/tests/Feature/Services/Theme/M7ThemeBrandingDesignSystemTest.php';

$enums = [
    ['ThemeDefinitionStatus', ['draft', 'published', 'archived']],
    ['ThemeVersionStatus', ['draft', 'published', 'archived']],
    ['ThemeInheritanceMode', ['none', 'merge_parent', 'override_parent']],
    ['ThemeScope', ['organization', 'workspace', 'application']],
];

$dtos = [
    'ThemeDefinition',
    'BrandProfile',
    'ThemeVersion',
    'ThemeRenderPayload',
    'ThemeStatistics',
    'ThemeHealthReport',
];

$permissions = ['themes.read', 'themes.manage', 'themes.publish', 'themes.brand'];

$body = <<<'PHP'
<?php

namespace Tests\Feature\Services\Theme;

use App\Models\Permission;
use App\Modules\Sdk\Theme\Contracts\ThemeRegistry;
use App\Modules\Sdk\Theme\Contracts\ThemeRenderer;
use App\Modules\Sdk\Theme\Contracts\ThemePublisher;
use App\Modules\Sdk\Theme\Contracts\ThemeVersionManager;
use App\Modules\Sdk\Theme\Contracts\ThemeInheritanceResolver;
use App\Services\Module\ModuleDoctorService;
use App\Services\Theme\ThemeRegistryService;
use App\Services\Theme\ThemeRendererService;
use App\Services\Theme\ThemePublisherService;
use App\Services\Theme\ThemeVersionService;
use App\Services\Theme\ThemeInheritanceResolverService;
use App\Services\Theme\ThemeDevelopmentService;
use App\Services\Theme\ThemePermissionBridge;
use App\Services\Theme\ThemeUiBridge;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7ThemeBrandingDesignSystemTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

PHP;

foreach ($enums as [$enum, $values]) {
    $body .= "\n    public function test_{$enum}_enum_has_expected_values(): void\n    {\n";
    $body .= "        \$cases = array_map(static fn (\\App\\Modules\\Sdk\\Theme\\Enums\\{$enum} \$case) => \$case->value, \\App\\Modules\\Sdk\\Theme\\Enums\\{$enum}::cases());\n";
    foreach ($values as $value) {
        $body .= "        \$this->assertContains('{$value}', \$cases);\n";
    }
    $body .= "    }\n";
}

foreach ($dtos as $dto) {
    $body .= "\n    public function test_{$dto}_dto_roundtrip(): void\n    {\n";
    $body .= "        \$sample = \\App\\Modules\\Sdk\\Theme\\Data\\{$dto}::fromArray([]);\n";
    $body .= "        \$roundtrip = \\App\\Modules\\Sdk\\Theme\\Data\\{$dto}::fromArray(\$sample->toArray());\n";
    $body .= "        \$this->assertSame(\$sample->toArray(), \$roundtrip->toArray());\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

    public function test_theme_contracts_bound(): void
    {
        $this->assertInstanceOf(ThemeRegistryService::class, app(ThemeRegistry::class));
        $this->assertInstanceOf(ThemeRendererService::class, app(ThemeRenderer::class));
        $this->assertInstanceOf(ThemePublisherService::class, app(ThemePublisher::class));
        $this->assertInstanceOf(ThemeVersionService::class, app(ThemeVersionManager::class));
        $this->assertInstanceOf(ThemeInheritanceResolverService::class, app(ThemeInheritanceResolver::class));
    }

    public function test_themes_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.themes.enabled', true));
    }

    public function test_permission_catalog_count_is_125(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(125, Permission::query()->count());
    }

    public function test_module_doctor_includes_themes_section(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('themes', $report->platformSummary['enterprise']);
    }

    public function test_register_theme_definition_from_array(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo',
            'theme_key' => 'default',
            'name' => 'Default Theme',
            'tokens' => ['color.primary' => '#2563eb'],
        ]);
        $this->assertSame('default', $theme->themeKey);
        $this->assertNotEmpty($theme->publicId);
    }

    public function test_list_theme_definitions(): void
    {
        $context = $this->themeContext();
        app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'one', 'name' => 'One',
        ]);
        $this->assertCount(1, app(ThemeDevelopmentService::class)->listDefinitions($context));
    }

    public function test_find_theme_definition_by_public_id(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'find-me', 'name' => 'Find Me',
        ]);
        $found = app(ThemeDevelopmentService::class)->findDefinitionByPublicId($context, $theme->publicId);
        $this->assertSame($theme->publicId, $found->publicId);
    }

    public function test_update_theme_definition(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'patch', 'name' => 'Before',
        ]);
        $updated = app(ThemeDevelopmentService::class)->updateDefinitionByPublicId($context, $theme->publicId, [
            'name' => 'After',
        ]);
        $this->assertSame('After', $updated->name);
    }

    public function test_update_and_list_brand_profiles(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'brand', 'name' => 'Brand Theme',
        ]);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, [
            'name' => 'HEOS Brand',
            'colors' => ['primary' => '#111111'],
        ]);
        $list = app(ThemeDevelopmentService::class)->listBrandProfiles($context);
        $this->assertSame('HEOS Brand', $profile->name);
        $this->assertCount(1, $list);
    }

    public function test_find_brand_profile_by_public_id(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'brand-find', 'name' => 'Brand Find',
        ]);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'Find Brand']);
        $found = app(ThemeDevelopmentService::class)->findBrandProfileByPublicId($context, $profile->publicId);
        $this->assertSame($profile->publicId, $found->publicId);
    }

    public function test_create_theme_version_and_list_versions(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'versions', 'name' => 'Versions',
        ]);
        app(ThemeDevelopmentService::class)->createThemeVersion($context, $theme->publicId, [
            'tokens' => ['x' => '1'],
        ]);
        $versions = app(ThemeDevelopmentService::class)->listVersionsForDefinition($context, $theme->publicId);
        $this->assertCount(1, $versions);
        $this->assertSame('draft', $versions[0]->status);
    }

    public function test_publish_theme_definition(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'publish', 'name' => 'Publish',
        ]);
        app(ThemeDevelopmentService::class)->createThemeVersion($context, $theme->publicId, ['tokens' => ['y' => '2']]);
        $published = app(ThemeDevelopmentService::class)->publishDefinition($context, $theme->publicId);
        $this->assertSame('published', $published->status);
        $this->assertNotNull($published->currentVersionPublicId);
    }

    public function test_render_theme_definition(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'render', 'name' => 'Render',
            'tokens' => ['color.primary' => '#000000'],
        ]);
        $payload = app(ThemeDevelopmentService::class)->renderDefinition($context, $theme->publicId);
        $this->assertArrayHasKey('tokens', $payload->theme);
    }

    public function test_runtime_composition_contains_source(): void
    {
        $context = $this->themeContext();
        $runtime = app(ThemeDevelopmentService::class)->composeRuntime($context);
        $this->assertSame('theme_framework', $runtime['source']);
    }

    public function test_theme_health_and_statistics(): void
    {
        $context = $this->themeContext();
        $health = app(ThemeDevelopmentService::class)->health($context);
        $stats = app(ThemeDevelopmentService::class)->statistics($context);
        $this->assertTrue($health->enabled);
        $this->assertSame(0, $stats->definitions);
    }

    public function test_inheritance_merge_parent_mode(): void
    {
        $context = $this->themeContext();
        $parent = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'parent', 'name' => 'Parent', 'tokens' => ['a' => 1, 'b' => 2],
        ]);
        $child = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'child', 'name' => 'Child',
            'inheritance_mode' => 'merge_parent', 'parent_theme_public_id' => $parent->publicId, 'tokens' => ['b' => 3],
        ]);
        $resolved = app(ThemeDevelopmentService::class)->renderDefinition($context, $child->publicId);
        $this->assertSame(1, $resolved->theme['tokens']['a'] ?? null);
        $this->assertSame(3, $resolved->theme['tokens']['b'] ?? null);
    }

    public function test_inheritance_override_parent_mode(): void
    {
        $context = $this->themeContext();
        $parent = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'parent2', 'name' => 'Parent2', 'tokens' => ['a' => 1],
        ]);
        $child = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'child2', 'name' => 'Child2',
            'inheritance_mode' => 'override_parent', 'parent_theme_public_id' => $parent->publicId, 'tokens' => ['c' => 9],
        ]);
        $resolved = app(ThemeDevelopmentService::class)->renderDefinition($context, $child->publicId);
        $this->assertArrayNotHasKey('a', $resolved->theme['tokens']);
        $this->assertSame(9, $resolved->theme['tokens']['c'] ?? null);
    }

    public function test_inheritance_cycle_warning(): void
    {
        $context = $this->themeContext();
        $a = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'cycle-a', 'name' => 'A', 'inheritance_mode' => 'merge_parent',
        ]);
        $b = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'cycle-b', 'name' => 'B',
            'inheritance_mode' => 'merge_parent', 'parent_theme_public_id' => $a->publicId,
        ]);
        app(ThemeDevelopmentService::class)->updateDefinitionByPublicId($context, $a->publicId, ['parent_theme_public_id' => $b->publicId]);
        $payload = app(ThemeDevelopmentService::class)->renderDefinition($context, $a->publicId);
        $this->assertNotEmpty($payload->warnings);
    }

    public function test_theme_ui_bridge_resolves_safe_default(): void
    {
        $context = $this->themeContext();
        $resolved = app(ThemeUiBridge::class)->resolveForPage($context, 'demo', 'missing-page', []);
        $this->assertArrayHasKey('tokens', $resolved['theme']);
    }

    public function test_theme_ui_bridge_preserves_page_override(): void
    {
        $context = $this->themeContext();
        $resolved = app(ThemeUiBridge::class)->resolveForPage($context, 'demo', 'any', [
            'tokens' => ['color.primary' => '#123456'],
        ]);
        $this->assertSame('#123456', $resolved['theme']['tokens']['color.primary'] ?? null);
    }

    public function test_missing_table_guard_in_health(): void
    {
        Schema::drop('theme_definitions');
        $health = app(\App\Services\Theme\ThemeHealthService::class)->health();
        $this->assertSame('warning', $health->status);
        $this->assertContains('theme_definitions', $health->missingTables);
    }

    public function test_member_has_read_theme_permission_only(): void
    {
        $owner = $this->themeContext();
        $member = $this->memberContext($owner);
        $bridge = app(ThemePermissionBridge::class);
        $this->assertTrue($bridge->canRead($member));
        $this->assertFalse($bridge->canManage($member));
        $this->assertFalse($bridge->canPublish($member));
        $this->assertFalse($bridge->canManageBrand($member));
    }

    public function test_viewer_has_read_theme_permission_only(): void
    {
        $owner = $this->themeContext();
        $viewer = $this->viewerContext($owner);
        $bridge = app(ThemePermissionBridge::class);
        $this->assertTrue($bridge->canRead($viewer));
        $this->assertFalse($bridge->canManage($viewer));
        $this->assertFalse($bridge->canPublish($viewer));
        $this->assertFalse($bridge->canManageBrand($viewer));
    }

    public function test_tenant_isolation_for_themes(): void
    {
        $contextA = $this->themeContext();
        app(ThemeDevelopmentService::class)->registerDefinition($contextA, [
            'module_key' => 'demo', 'theme_key' => 'iso', 'name' => 'ISO',
        ]);
        $contextB = $this->themeContext();
        $definitions = app(ThemeDevelopmentService::class)->listDefinitions($contextB);
        $this->assertSame([], $definitions);
    }

    public function test_workspace_isolation_for_themes(): void
    {
        $context = $this->themeContext();
        app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'ws', 'name' => 'WS',
        ]);
        $other = $this->otherWorkspaceContext($context);
        $definitions = app(ThemeDevelopmentService::class)->listDefinitions($other);
        $this->assertSame([], $definitions);
    }

PHP;

foreach ($permissions as $permission) {
    $slug = str_replace('.', '_', $permission);
    $body .= "\n    public function test_permission_exists_{$slug}(): void\n    {\n";
    $body .= "        \$this->seedHeosPlatform();\n";
    $body .= "        \$this->assertNotNull(Permission::query()->where('key', '{$permission}')->first());\n";
    $body .= "    }\n";
}

$apiTests = [
    ['api_health_endpoint', "getJson('/api/v1/tenant/themes/health')->assertOk();"],
    ['api_runtime_endpoint', "getJson('/api/v1/tenant/themes/runtime')->assertOk();"],
    ['api_statistics_endpoint', "getJson('/api/v1/tenant/themes/statistics')->assertOk();"],
    ['api_index_themes', "getJson('/api/v1/tenant/themes')->assertOk();"],
    ['api_store_theme', "postJson('/api/v1/tenant/themes', ['module_key' => 'demo', 'theme_key' => 'api', 'name' => 'API'])->assertCreated();"],
    ['api_show_theme', "getJson('/api/v1/tenant/themes/'.\$theme->publicId)->assertOk();"],
    ['api_patch_theme', "patchJson('/api/v1/tenant/themes/'.\$theme->publicId, ['name' => 'Patched'])->assertOk();"],
    ['api_store_brand_profile', "postJson('/api/v1/tenant/themes/'.\$theme->publicId.'/brand-profile', ['name' => 'Brand'])->assertOk();"],
    ['api_index_brand_profiles', "getJson('/api/v1/tenant/brand-profiles')->assertOk();"],
    ['api_show_brand_profile', "getJson('/api/v1/tenant/brand-profiles/'.\$profile->publicId)->assertOk();"],
    ['api_store_version', "postJson('/api/v1/tenant/themes/'.\$theme->publicId.'/versions', ['snapshot' => ['tokens' => ['x' => '1']]])->assertCreated();"],
    ['api_index_versions', "getJson('/api/v1/tenant/themes/'.\$theme->publicId.'/versions')->assertOk();"],
    ['api_publish_theme', "postJson('/api/v1/tenant/themes/'.\$theme->publicId.'/publish')->assertOk();"],
    ['api_render_theme', "getJson('/api/v1/tenant/themes/'.\$theme->publicId.'/render')->assertOk();"],
    ['api_static_routes_precede_parameterized', "getJson('/api/v1/tenant/themes/health')->assertOk();"],
];

foreach ($apiTests as [$name, $assertion]) {
    $body .= "\n    public function test_{$name}(): void\n    {\n";
    $body .= "        \$context = \$this->themeContext();\n";
    $body .= "        \$headers = \$this->tenantHeaders(\$context);\n";
    $body .= "        \$theme = app(ThemeDevelopmentService::class)->registerDefinition(\$context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);\n";
    $body .= "        \$profile = app(ThemeDevelopmentService::class)->updateBrandProfile(\$context, \$theme->publicId, ['name' => 'API Brand']);\n";
    $body .= "        \$this->withHeaders(\$headers)->{$assertion}\n";
    $body .= "    }\n";
}

$serviceClassSmoke = [
    '\\App\\Services\\Theme\\ThemeMapper',
    '\\App\\Services\\Theme\\ThemeTableHealthSupport',
    '\\App\\Services\\Theme\\ThemeAuditRecorder',
    '\\App\\Services\\Theme\\ThemeSearchIndexer',
    '\\App\\Services\\Theme\\ThemePlatformEventBridge',
    '\\App\\Services\\Theme\\ThemeStatisticsService',
    '\\App\\Services\\Theme\\ThemeHealthService',
    '\\App\\Services\\Theme\\ThemePermissionBridge',
    '\\App\\Services\\Theme\\ThemeApplicationBridge',
    '\\App\\Services\\Theme\\ThemeNavigationBridge',
    '\\App\\Services\\Theme\\ThemeDocumentBridge',
    '\\App\\Services\\Theme\\ThemeUiBridge',
];

foreach ($serviceClassSmoke as $index => $className) {
    $body .= "\n    public function test_service_class_exists_{$index}(): void\n    {\n";
    $body .= "        \$this->assertTrue(class_exists('{$className}'));\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->themeContext();
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/themes')
            ->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    private function themeContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'themes-'.uniqid()]);
        $context = $this->buildTenantContext($user, $result);
        app()->instance(TenantContext::class, $context);

        return $context;
    }

    /** @return array<string, string> */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }

    private function memberContext(TenantContext $owner): TenantContext
    {
        $member = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $owner->organization->id)
            ->where('key', 'member')
            ->firstOrFail();
        $membership = $owner->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $owner->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);
        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels($member, $owner->organization, $membership, $owner->workspace);
    }

    private function viewerContext(TenantContext $owner): TenantContext
    {
        $viewer = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $owner->organization->id)
            ->where('key', 'viewer')
            ->firstOrFail();
        $membership = $owner->organization->memberships()->create([
            'user_id' => $viewer->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $owner->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);
        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels($viewer, $owner->organization, $membership, $owner->workspace);
    }

    private function otherWorkspaceContext(TenantContext $owner): TenantContext
    {
        $workspace = $owner->organization->workspaces()->create([
            'public_id' => (string) \Illuminate\Support\Str::uuid7(),
            'name' => 'Other Workspace',
            'slug' => 'other-'.uniqid(),
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        return TenantContext::fromModels($owner->user, $owner->organization, $owner->membership, $workspace);
    }
}

PHP;

if (! is_dir(dirname($testPath))) {
    mkdir(dirname($testPath), 0777, true);
}

file_put_contents($testPath, $body);
echo 'Wrote '.substr_count($body, 'public function test_')." tests to {$testPath}\n";
