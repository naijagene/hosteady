<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$testPath = $base.'/tests/Feature/Services/Personalization/M7PersonalizationExperienceTest.php';

$serviceClassSmoke = [
    '\\App\\Services\\Personalization\\PersonalizationMapper',
    '\\App\\Services\\Personalization\\PersonalizationTableHealthSupport',
    '\\App\\Services\\Personalization\\PersonalizationAuditRecorder',
    '\\App\\Services\\Personalization\\PersonalizationSearchIndexer',
    '\\App\\Services\\Personalization\\PersonalizationPlatformEventBridge',
    '\\App\\Services\\Personalization\\PersonalizationProfileService',
    '\\App\\Services\\Personalization\\PreferenceService',
    '\\App\\Services\\Personalization\\FavoriteService',
    '\\App\\Services\\Personalization\\RecentActivityService',
    '\\App\\Services\\Personalization\\ShortcutService',
    '\\App\\Services\\Personalization\\QuickActionService',
    '\\App\\Services\\Personalization\\OnboardingService',
    '\\App\\Services\\Personalization\\DismissedTipService',
    '\\App\\Services\\Personalization\\PersonalizationRuntimeComposerService',
    '\\App\\Services\\Personalization\\PersonalizationStatisticsService',
    '\\App\\Services\\Personalization\\PersonalizationHealthService',
    '\\App\\Services\\Personalization\\PersonalizationDevelopmentService',
    '\\App\\Services\\Personalization\\PersonalizationApplicationBridge',
    '\\App\\Services\\Personalization\\PersonalizationUiBridge',
    '\\App\\Services\\Personalization\\PersonalizationNavigationBridge',
    '\\App\\Services\\Personalization\\PersonalizationThemeBridge',
    '\\App\\Services\\Personalization\\PersonalizationDashboardBridge',
    '\\App\\Services\\Personalization\\PersonalizationTableBridge',
    '\\App\\Services\\Personalization\\PersonalizationNotificationBridge',
];

$permissions = ['personalization.read', 'personalization.write', 'personalization.manage', 'personalization.admin'];

$body = <<<'PHP'
<?php

namespace Tests\Feature\Services\Personalization;

use App\Models\Permission;
use App\Services\Module\ModuleDoctorService;
use App\Services\Personalization\FavoriteService;
use App\Services\Personalization\OnboardingService;
use App\Services\Personalization\PersonalizationDevelopmentService;
use App\Services\Personalization\PersonalizationRuntimeComposerService;
use App\Services\Personalization\PreferenceService;
use App\Services\Personalization\RecentActivityService;
use App\Services\Personalization\ShortcutService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7PersonalizationExperienceTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_personalization_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.personalization.enabled', true));
    }

    public function test_permission_catalog_count_is_129(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(129, Permission::query()->count());
    }

    public function test_module_doctor_includes_personalization_section(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('personalization', $report->platformSummary['enterprise']);
    }

    public function test_runtime_composer_returns_precedence_order(): void
    {
        $context = $this->context();
        $runtime = app(PersonalizationRuntimeComposerService::class)->compose($context);
        $this->assertSame(
            ['global', 'organization', 'application', 'workspace', 'membership', 'user'],
            $runtime->capabilities['precedence'] ?? [],
        );
    }

    public function test_preference_upsert_supports_all_basic_types(): void
    {
        $context = $this->context();
        $service = app(PreferenceService::class);
        $service->upsert($context, 'a', 'string', 'x');
        $service->upsert($context, 'b', 'boolean', true);
        $service->upsert($context, 'c', 'integer', 10);
        $service->upsert($context, 'd', 'decimal', 3.14);
        $service->upsert($context, 'e', 'map', ['x' => 1]);
        $service->upsert($context, 'f', 'list', [1, 2]);
        $this->assertCount(6, $service->list($context));
    }

    public function test_favorite_add_list_remove(): void
    {
        $context = $this->context();
        $service = app(FavoriteService::class);
        $favorite = $service->add($context, 'module', (string) \Illuminate\Support\Str::uuid7(), 'Demo');
        $this->assertCount(1, $service->list($context));
        $service->remove($context, $favorite->publicId);
        $this->assertCount(0, $service->list($context));
    }

    public function test_recent_record_deduplicates_subject_reference(): void
    {
        $context = $this->context();
        $service = app(RecentActivityService::class);
        $subjectId = (string) \Illuminate\Support\Str::uuid7();
        $service->record($context, 'document', $subjectId, 'Doc 1');
        $service->record($context, 'document', $subjectId, 'Doc 1');
        $this->assertCount(1, $service->list($context));
    }

    public function test_shortcut_create_update_delete(): void
    {
        $context = $this->context();
        $service = app(ShortcutService::class);
        $shortcut = $service->create($context, ['label' => 'Home', 'route' => '/home']);
        $updated = $service->update($context, $shortcut->publicId, ['label' => 'Workspace Home']);
        $this->assertSame('Workspace Home', $updated->label);
        $service->delete($context, $shortcut->publicId);
        $this->assertCount(0, $service->list($context));
    }

    public function test_onboarding_start_step_complete_reset(): void
    {
        $context = $this->context();
        $service = app(OnboardingService::class);
        $state = $service->start($context, 'welcome');
        $this->assertSame('started', $state->status);
        $state = $service->step($context, 'welcome', 'profile');
        $this->assertSame('in_progress', $state->status);
        $state = $service->complete($context, 'welcome');
        $this->assertSame('completed', $state->status);
        $state = $service->reset($context, 'welcome');
        $this->assertSame('started', $state->status);
    }

    public function test_missing_table_health_fallback(): void
    {
        Schema::drop('personalization_profiles');
        $health = app(\App\Services\Personalization\PersonalizationHealthService::class)->health();
        $this->assertSame('warning', $health->status);
        $this->assertContains('personalization_profiles', $health->missingTables);
    }

    public function test_runtime_payload_contains_expected_sections(): void
    {
        $context = $this->context();
        $payload = app(PersonalizationDevelopmentService::class)->runtime($context)->toArray();
        $this->assertArrayHasKey('profile', $payload);
        $this->assertArrayHasKey('preferences', $payload);
        $this->assertArrayHasKey('favorites', $payload);
        $this->assertArrayHasKey('recent', $payload);
        $this->assertArrayHasKey('shortcuts', $payload);
        $this->assertArrayHasKey('quick_actions', $payload);
        $this->assertArrayHasKey('onboarding', $payload);
    }

    public function test_tenant_isolation_for_preferences(): void
    {
        $ctxA = $this->context();
        app(PreferenceService::class)->upsert($ctxA, 'theme.mode', 'string', 'dark');
        $ctxB = $this->context();
        $this->assertCount(0, app(PreferenceService::class)->list($ctxB));
    }

    public function test_workspace_isolation_for_shortcuts(): void
    {
        $context = $this->context();
        app(ShortcutService::class)->create($context, ['label' => 'A']);
        $other = $this->otherWorkspaceContext($context);
        $this->assertCount(0, app(ShortcutService::class)->list($other));
    }
PHP;

foreach ($permissions as $permission) {
    $slug = str_replace('.', '_', $permission);
    $body .= "\n\n    public function test_permission_exists_{$slug}(): void\n    {\n";
    $body .= "        \$this->seedHeosPlatform();\n";
    $body .= "        \$this->assertNotNull(Permission::query()->where('key', '{$permission}')->first());\n";
    $body .= "    }\n";
}

for ($i = 0; $i < count($serviceClassSmoke); $i++) {
    $class = $serviceClassSmoke[$i];
    $body .= "\n\n    public function test_service_class_exists_{$i}(): void\n    {\n";
    $body .= "        \$this->assertTrue(class_exists('{$class}'));\n";
    $body .= "    }\n";
}

$routeAssertions = [
    "getJson('/api/v1/tenant/personalization/runtime')->assertOk();",
    "getJson('/api/v1/tenant/personalization/health')->assertOk();",
    "getJson('/api/v1/tenant/personalization/statistics')->assertOk();",
    "getJson('/api/v1/tenant/personalization/preferences')->assertOk();",
    "patchJson('/api/v1/tenant/personalization/preferences', ['preferences' => ['theme.mode' => 'dark']])->assertOk();",
    "getJson('/api/v1/tenant/personalization/favorites')->assertOk();",
    "postJson('/api/v1/tenant/personalization/favorites', ['subject_type' => 'module', 'subject_public_id' => (string) \\Illuminate\\Support\\Str::uuid7()])->assertCreated();",
    "getJson('/api/v1/tenant/personalization/recent')->assertOk();",
    "postJson('/api/v1/tenant/personalization/recent', ['subject_type' => 'document', 'subject_public_id' => (string) \\Illuminate\\Support\\Str::uuid7()])->assertCreated();",
    "getJson('/api/v1/tenant/personalization/shortcuts')->assertOk();",
    "postJson('/api/v1/tenant/personalization/shortcuts', ['label' => 'Open Dashboard', 'route' => '/dashboard'])->assertCreated();",
    "getJson('/api/v1/tenant/personalization/onboarding')->assertOk();",
    "postJson('/api/v1/tenant/personalization/onboarding/start', ['flow_key' => 'welcome'])->assertOk();",
    "postJson('/api/v1/tenant/personalization/onboarding/step', ['flow_key' => 'welcome', 'step' => 'profile'])->assertOk();",
    "postJson('/api/v1/tenant/personalization/onboarding/complete', ['flow_key' => 'welcome'])->assertOk();",
    "postJson('/api/v1/tenant/personalization/onboarding/reset', ['flow_key' => 'welcome'])->assertOk();",
];

foreach ($routeAssertions as $index => $assertion) {
    $body .= "\n\n    public function test_api_route_{$index}(): void\n    {\n";
    $body .= "        \$context = \$this->context();\n";
    $body .= "        \$headers = \$this->tenantHeaders(\$context);\n";
    $body .= "        \$this->withHeaders(\$headers)->{$assertion}\n";
    $body .= "    }\n";
}

$body .= <<<'PHP'

    private function context(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'personalization-'.uniqid()]);
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
