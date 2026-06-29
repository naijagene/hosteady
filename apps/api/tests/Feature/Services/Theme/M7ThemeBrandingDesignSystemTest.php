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

    public function test_ThemeDefinitionStatus_enum_has_expected_values(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus $case) => $case->value, \App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus::cases());
        $this->assertContains('draft', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_ThemeVersionStatus_enum_has_expected_values(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Theme\Enums\ThemeVersionStatus $case) => $case->value, \App\Modules\Sdk\Theme\Enums\ThemeVersionStatus::cases());
        $this->assertContains('draft', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_ThemeInheritanceMode_enum_has_expected_values(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode $case) => $case->value, \App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode::cases());
        $this->assertContains('none', $cases);
        $this->assertContains('merge_parent', $cases);
        $this->assertContains('override_parent', $cases);
    }

    public function test_ThemeScope_enum_has_expected_values(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Theme\Enums\ThemeScope $case) => $case->value, \App\Modules\Sdk\Theme\Enums\ThemeScope::cases());
        $this->assertContains('organization', $cases);
        $this->assertContains('workspace', $cases);
        $this->assertContains('application', $cases);
    }

    public function test_ThemeDefinition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\ThemeDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\ThemeDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_BrandProfile_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\BrandProfile::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\BrandProfile::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ThemeVersion_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\ThemeVersion::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\ThemeVersion::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ThemeRenderPayload_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\ThemeRenderPayload::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\ThemeRenderPayload::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ThemeStatistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\ThemeStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\ThemeStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ThemeHealthReport_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Theme\Data\ThemeHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Theme\Data\ThemeHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

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
        Schema::drop('theme_versions');
        Schema::drop('brand_profiles');
        $health = app(\App\Services\Theme\ThemeHealthService::class)->health();
        $this->assertSame('warning', $health->status);
        $this->assertContains('theme_definitions', $health->missingTables);
        $this->assertStringContainsString('Run php artisan migrate.', $health->warnings[0]);
    }

    public function test_health_reports_missing_theme_tables(): void
    {
        Schema::drop('theme_definitions');
        Schema::drop('theme_versions');
        Schema::drop('brand_profiles');

        $health = app(\App\Services\Theme\ThemeHealthService::class)->health();

        $this->assertSame('warning', $health->status);
        $this->assertContains('theme_definitions', $health->missingTables);
        $this->assertContains('theme_versions', $health->missingTables);
        $this->assertContains('brand_profiles', $health->missingTables);
    }

    public function test_registry_list_does_not_crash_when_theme_definitions_is_missing(): void
    {
        Schema::drop('theme_definitions');
        $context = $this->themeContext();
        $list = app(ThemeRegistryService::class)->list($context->organization->id, $context->workspace->id);
        $this->assertSame([], $list);
    }

    public function test_brand_profile_list_does_not_crash_when_brand_profiles_is_missing(): void
    {
        Schema::drop('brand_profiles');
        $context = $this->themeContext();
        $list = app(ThemeDevelopmentService::class)->listBrandProfiles($context);
        $this->assertSame([], $list);
    }

    public function test_version_list_does_not_crash_when_theme_versions_is_missing(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'ver-missing', 'name' => 'Ver Missing',
        ]);
        Schema::drop('theme_versions');
        $versions = app(ThemeDevelopmentService::class)->listVersionsForDefinition($context, $theme->publicId);
        $this->assertSame([], $versions);
    }

    public function test_self_parent_theme_is_rejected(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'self', 'name' => 'Self',
        ]);

        $this->expectException(\App\Modules\Sdk\Theme\Exceptions\ThemeValidationException::class);
        app(ThemeDevelopmentService::class)->updateDefinitionByPublicId($context, $theme->publicId, [
            'parent_theme_public_id' => $theme->publicId,
            'inheritance_mode' => 'merge_parent',
        ]);
    }

    public function test_inheritance_depth_limit_returns_warning(): void
    {
        $context = $this->themeContext();
        $dev = app(ThemeDevelopmentService::class);
        $parent = null;
        $deepest = null;

        for ($i = 0; $i < 27; $i++) {
            $data = [
                'module_key' => 'demo',
                'theme_key' => 'depth-'.$i,
                'name' => 'Depth '.$i,
                'inheritance_mode' => $i === 0 ? 'none' : 'merge_parent',
            ];

            if ($parent !== null) {
                $data['parent_theme_public_id'] = $parent->publicId;
            }

            $deepest = $dev->registerDefinition($context, $data);
            $parent = $deepest;
        }

        $payload = $dev->renderDefinition($context, $deepest->publicId);
        $this->assertNotEmpty($payload->warnings);
        $this->assertTrue(collect($payload->warnings)->contains(
            fn (string $warning): bool => str_contains($warning, 'depth exceeds limit'),
        ));
    }

    public function test_brand_asset_metadata_requires_type_and_alt(): void
    {
        $context = $this->themeContext();
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo', 'theme_key' => 'brand-asset', 'name' => 'Brand Asset',
        ]);

        $this->expectException(\App\Modules\Sdk\Theme\Exceptions\ThemeValidationException::class);
        app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, [
            'assets' => [['url' => 'https://example.com/logo.png']],
        ]);
    }

    public function test_heos_doctor_includes_themes_missing_table_warning(): void
    {
        Schema::drop('theme_definitions');
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('themes', $report->platformSummary['enterprise']);
        $this->assertContains(
            'theme_definitions',
            $report->platformSummary['enterprise']['themes']['missing_tables'],
        );
    }

    public function test_runtime_endpoint_returns_resolved_published_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, [
            'module_key' => 'demo',
            'theme_key' => 'runtime-default',
            'name' => 'Runtime Default',
            'tokens' => ['color.primary' => '#abcdef'],
        ]);
        app(ThemeDevelopmentService::class)->publishDefinition($context, $theme->publicId);
        app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'Runtime Brand']);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/themes/runtime?theme_key=runtime-default&module_key=demo');

        $response->assertOk()
            ->assertJsonPath('data.brand_profile.name', 'Runtime Brand')
            ->assertJsonPath('data.source', 'theme_framework');
        $this->assertSame('#abcdef', $response->json('data.theme.tokens')['color.primary'] ?? null);
    }

    public function test_runtime_endpoint_returns_safe_default_when_no_theme_exists(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/themes/runtime?theme_key=nonexistent-theme')
            ->assertOk()
            ->assertJsonPath('data.theme.source', 'safe_default')
            ->assertJsonStructure(['data' => ['theme' => ['tokens']]]);
    }

    public function test_runtime_endpoint_returns_warnings_when_theme_tables_are_missing(): void
    {
        Schema::drop('theme_definitions');
        Schema::drop('theme_versions');
        Schema::drop('brand_profiles');
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);

        $response = $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/runtime');
        $response->assertOk()
            ->assertJsonPath('data.runtime_context.status', 'warning');
        $this->assertNotEmpty($response->json('data.warnings'));
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

    public function test_permission_exists_themes_read(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'themes.read')->first());
    }

    public function test_permission_exists_themes_manage(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'themes.manage')->first());
    }

    public function test_permission_exists_themes_publish(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'themes.publish')->first());
    }

    public function test_permission_exists_themes_brand(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'themes.brand')->first());
    }

    public function test_api_health_endpoint(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/health')->assertOk();
    }

    public function test_api_runtime_endpoint(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/runtime')->assertOk();
    }

    public function test_api_statistics_endpoint(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/statistics')->assertOk();
    }

    public function test_api_index_themes(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes')->assertOk();
    }

    public function test_api_store_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/themes', ['module_key' => 'demo', 'theme_key' => 'api', 'name' => 'API'])->assertCreated();
    }

    public function test_api_show_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/'.$theme->publicId)->assertOk();
    }

    public function test_api_patch_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->patchJson('/api/v1/tenant/themes/'.$theme->publicId, ['name' => 'Patched'])->assertOk();
    }

    public function test_api_store_brand_profile(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/themes/'.$theme->publicId.'/brand-profile', ['name' => 'Brand'])->assertOk();
    }

    public function test_api_index_brand_profiles(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/brand-profiles')->assertOk();
    }

    public function test_api_show_brand_profile(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/brand-profiles/'.$profile->publicId)->assertOk();
    }

    public function test_api_store_version(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/themes/'.$theme->publicId.'/versions', ['snapshot' => ['tokens' => ['x' => '1']]])->assertCreated();
    }

    public function test_api_index_versions(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/'.$theme->publicId.'/versions')->assertOk();
    }

    public function test_api_publish_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/themes/'.$theme->publicId.'/publish')->assertOk();
    }

    public function test_api_render_theme(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/'.$theme->publicId.'/render')->assertOk();
    }

    public function test_api_static_routes_precede_parameterized(): void
    {
        $context = $this->themeContext();
        $headers = $this->tenantHeaders($context);
        $theme = app(ThemeDevelopmentService::class)->registerDefinition($context, ['module_key' => 'demo', 'theme_key' => 'api-'.uniqid(), 'name' => 'API Theme']);
        $profile = app(ThemeDevelopmentService::class)->updateBrandProfile($context, $theme->publicId, ['name' => 'API Brand']);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/themes/health')->assertOk();
    }

    public function test_service_class_exists_0(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeMapper'));
    }

    public function test_service_class_exists_1(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeTableHealthSupport'));
    }

    public function test_service_class_exists_2(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeAuditRecorder'));
    }

    public function test_service_class_exists_3(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeSearchIndexer'));
    }

    public function test_service_class_exists_4(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemePlatformEventBridge'));
    }

    public function test_service_class_exists_5(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeStatisticsService'));
    }

    public function test_service_class_exists_6(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeHealthService'));
    }

    public function test_service_class_exists_7(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemePermissionBridge'));
    }

    public function test_service_class_exists_8(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeApplicationBridge'));
    }

    public function test_service_class_exists_9(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeNavigationBridge'));
    }

    public function test_service_class_exists_10(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeDocumentBridge'));
    }

    public function test_service_class_exists_11(): void
    {
        $this->assertTrue(class_exists('\App\Services\Theme\ThemeUiBridge'));
    }

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
