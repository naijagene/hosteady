<?php

namespace Tests\Feature\Services\Personalization;

use App\Models\Permission;
use App\Services\Module\ModuleDoctorService;
use App\Services\Personalization\FavoriteService;
use App\Services\Personalization\OnboardingService;
use App\Services\Personalization\PersonalizationDevelopmentService;
use App\Services\Personalization\PersonalizationMapper;
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
        $payload = app(PersonalizationDevelopmentService::class)->runtime($context)->toApiArray();
        $this->assertArrayHasKey('preferences', $payload);
        $this->assertArrayHasKey('favorites', $payload);
        $this->assertArrayHasKey('recent_items', $payload);
        $this->assertArrayHasKey('shortcuts', $payload);
        $this->assertArrayHasKey('quick_actions', $payload);
        $this->assertArrayHasKey('onboarding_state', $payload);
        $this->assertArrayHasKey('theme_override', $payload);
        $this->assertArrayHasKey('navigation_overrides', $payload);
        $this->assertArrayHasKey('dashboard_overrides', $payload);
        $this->assertArrayHasKey('table_overrides', $payload);
        $this->assertArrayHasKey('notification_preferences_reference', $payload);
        $this->assertArrayHasKey('warnings', $payload);
        $this->assertArrayHasKey('source', $payload);
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

    public function test_permission_exists_personalization_read(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'personalization.read')->first());
    }


    public function test_permission_exists_personalization_write(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'personalization.write')->first());
    }


    public function test_permission_exists_personalization_manage(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'personalization.manage')->first());
    }


    public function test_permission_exists_personalization_admin(): void
    {
        $this->seedHeosPlatform();
        $this->assertNotNull(Permission::query()->where('key', 'personalization.admin')->first());
    }


    public function test_service_class_exists_0(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationMapper'));
    }


    public function test_service_class_exists_1(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationTableHealthSupport'));
    }


    public function test_service_class_exists_2(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationAuditRecorder'));
    }


    public function test_service_class_exists_3(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationSearchIndexer'));
    }


    public function test_service_class_exists_4(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationPlatformEventBridge'));
    }


    public function test_service_class_exists_5(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationProfileService'));
    }


    public function test_service_class_exists_6(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PreferenceService'));
    }


    public function test_service_class_exists_7(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\FavoriteService'));
    }


    public function test_service_class_exists_8(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\RecentActivityService'));
    }


    public function test_service_class_exists_9(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\ShortcutService'));
    }


    public function test_service_class_exists_10(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\QuickActionService'));
    }


    public function test_service_class_exists_11(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\OnboardingService'));
    }


    public function test_service_class_exists_12(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\DismissedTipService'));
    }


    public function test_service_class_exists_13(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationRuntimeComposerService'));
    }


    public function test_service_class_exists_14(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationStatisticsService'));
    }


    public function test_service_class_exists_15(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationHealthService'));
    }


    public function test_service_class_exists_16(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationDevelopmentService'));
    }


    public function test_service_class_exists_17(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationApplicationBridge'));
    }


    public function test_service_class_exists_18(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationUiBridge'));
    }


    public function test_service_class_exists_19(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationNavigationBridge'));
    }


    public function test_service_class_exists_20(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationThemeBridge'));
    }


    public function test_service_class_exists_21(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationDashboardBridge'));
    }


    public function test_service_class_exists_22(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationTableBridge'));
    }


    public function test_service_class_exists_23(): void
    {
        $this->assertTrue(class_exists('\App\Services\Personalization\PersonalizationNotificationBridge'));
    }


    public function test_api_route_0(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/runtime')->assertOk();
    }


    public function test_api_route_1(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/health')->assertOk();
    }


    public function test_api_route_2(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/statistics')->assertOk();
    }


    public function test_api_route_3(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/preferences')->assertOk();
    }


    public function test_api_route_4(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->patchJson('/api/v1/tenant/personalization/preferences', ['preferences' => ['theme.mode' => 'dark']])->assertOk();
    }


    public function test_api_route_5(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/favorites')->assertOk();
    }


    public function test_api_route_6(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/favorites', ['subject_type' => 'module', 'subject_public_id' => (string) \Illuminate\Support\Str::uuid7()])->assertCreated();
    }


    public function test_api_route_7(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/recent')->assertOk();
    }


    public function test_api_route_8(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/recent', ['subject_type' => 'document', 'subject_public_id' => (string) \Illuminate\Support\Str::uuid7()])->assertCreated();
    }


    public function test_api_route_9(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/shortcuts')->assertOk();
    }


    public function test_api_route_10(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/shortcuts', ['label' => 'Open Dashboard', 'route' => '/dashboard'])->assertCreated();
    }


    public function test_api_route_11(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/onboarding')->assertOk();
    }


    public function test_api_route_12(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/onboarding/start', ['flow_key' => 'welcome'])->assertOk();
    }


    public function test_api_route_13(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/onboarding/step', ['flow_key' => 'welcome', 'step' => 'profile'])->assertOk();
    }


    public function test_api_route_14(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/onboarding/complete', ['flow_key' => 'welcome'])->assertOk();
    }


    public function test_api_route_15(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $this->withHeaders($headers)->postJson('/api/v1/tenant/personalization/onboarding/reset', ['flow_key' => 'welcome'])->assertOk();
    }

    public function test_PersonalizationRuntimePayload_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\PersonalizationRuntimePayload::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_PersonalizationHealthReport_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\PersonalizationHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_PreferenceItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\PreferenceItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\PreferenceItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_FavoriteItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\FavoriteItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\FavoriteItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_RecentItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\RecentItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\RecentItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ShortcutItem_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\ShortcutItem::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\ShortcutItem::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_OnboardingState_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Personalization\Data\OnboardingState::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Personalization\Data\OnboardingState::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_preference_scope_precedence_resolves_later_scope(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'theme.mode', 'string', 'light', 'organization');
        app(PreferenceService::class)->upsert($context, 'theme.mode', 'string', 'dark', 'membership');
        $resolved = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $this->assertSame('dark', $resolved['theme.mode'] ?? null);
    }

    public function test_theme_override_bridge_reads_theme_public_id(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'theme_public_id', 'string', 'theme-123');
        $preferences = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $override = app(\App\Services\Personalization\PersonalizationThemeBridge::class)->resolve($context, $preferences);
        $this->assertSame('theme-123', $override['theme_public_id'] ?? null);
    }

    public function test_navigation_bridge_exposes_pinned_items(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'pinned_navigation_items', 'list', ['nav-1']);
        $preferences = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $overrides = app(\App\Services\Personalization\PersonalizationNavigationBridge::class)->resolve($context, $preferences);
        $this->assertSame(['nav-1'], $overrides['pinned_items'] ?? null);
    }

    public function test_dashboard_bridge_exposes_layout_override(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'dashboard_layout', 'json', ['columns' => 3]);
        $preferences = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $overrides = app(\App\Services\Personalization\PersonalizationDashboardBridge::class)->resolve($context, $preferences);
        $this->assertSame(['columns' => 3], $overrides['dashboard_layout'] ?? null);
    }

    public function test_table_bridge_exposes_density(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'table_density', 'string', 'compact');
        $preferences = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $overrides = app(\App\Services\Personalization\PersonalizationTableBridge::class)->resolve($context, $preferences);
        $this->assertSame('compact', $overrides['table_density'] ?? null);
    }

    public function test_notification_bridge_exposes_panel_position(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'notifications_panel_position', 'string', 'right');
        $preferences = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $overrides = app(\App\Services\Personalization\PersonalizationNotificationBridge::class)->resolve($context, $preferences);
        $this->assertSame('right', $overrides['notifications_panel_position'] ?? null);
    }

    public function test_quick_actions_metadata_generated(): void
    {
        $context = $this->context();
        $actions = app(\App\Services\Personalization\QuickActionService::class)->generate($context);
        $this->assertNotEmpty($actions);
        $this->assertSame('navigation', $actions[0]['type'] ?? null);
    }

    public function test_runtime_composer_includes_quick_actions(): void
    {
        $context = $this->context();
        $runtime = app(PersonalizationRuntimeComposerService::class)->compose($context);
        $this->assertNotEmpty($runtime->quickActions);
    }

    public function test_registry_list_does_not_crash_when_profiles_missing(): void
    {
        Schema::drop('personalization_profiles');
        $context = $this->context();
        $profile = app(\App\Services\Personalization\PersonalizationRegistryService::class)->ensureProfile($context);
        $this->assertNull($profile);
    }

    public function test_heos_doctor_includes_personalization_missing_table_warning(): void
    {
        Schema::drop('personalization_profiles');
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertContains(
            'personalization_profiles',
            $report->platformSummary['enterprise']['personalization']['missing_tables'] ?? [],
        );
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        app(PreferenceService::class)->upsert($context, 'locale', 'string', 'en');
        $response = $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/preferences');
        $response->assertOk();
        foreach ($response->json('data') ?? [] as $item) {
            $this->assertArrayHasKey('public_id', $item);
            $this->assertArrayNotHasKey('id', $item);
        }
    }

    public function test_runtime_endpoint_returns_safe_defaults(): void
    {
        $context = $this->context();
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/personalization/runtime')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'preferences', 'favorites', 'recent_items', 'shortcuts', 'quick_actions',
                    'onboarding_state', 'theme_override', 'navigation_overrides', 'dashboard_overrides',
                    'table_overrides', 'notification_preferences_reference', 'warnings', 'source',
                ],
            ])
            ->assertJsonPath('data.source', 'personalization_framework');
    }

    public function test_runtime_endpoint_returns_missing_table_warnings(): void
    {
        Schema::drop('personalization_profiles');
        Schema::drop('personalization_preferences');
        Schema::drop('personalization_favorites');
        Schema::drop('personalization_recent_items');
        Schema::drop('personalization_shortcuts');
        Schema::drop('personalization_onboarding_states');

        $context = $this->context();
        $headers = $this->tenantHeaders($context);
        $response = $this->withHeaders($headers)->getJson('/api/v1/tenant/personalization/runtime');

        $response->assertOk()
            ->assertJsonPath('data.source', 'safe_default')
            ->assertJsonPath('data.runtime_context.status', 'warning');
        $this->assertNotEmpty($response->json('data.warnings'));
    }

    public function test_preference_precedence_inherits_undefined_values(): void
    {
        $context = $this->context();
        app(PreferenceService::class)->upsert($context, 'locale', 'string', 'en-US', 'organization');
        app(PreferenceService::class)->upsert($context, 'theme.mode', 'string', '', 'membership');
        $resolved = PersonalizationMapper::resolvePreferences(app(PreferenceService::class)->list($context));
        $this->assertSame('en-US', $resolved['locale'] ?? null);
        $this->assertArrayNotHasKey('theme.mode', $resolved);
    }

    public function test_duplicate_favorite_updates_existing_entry(): void
    {
        $context = $this->context();
        $service = app(FavoriteService::class);
        $subjectId = (string) \Illuminate\Support\Str::uuid7();
        $first = $service->add($context, 'page', $subjectId, 'First', 1);
        $second = $service->add($context, 'page', $subjectId, 'Updated', 5);
        $this->assertSame($first->publicId, $second->publicId);
        $this->assertSame('Updated', $second->label);
        $this->assertCount(1, $service->list($context));
    }

    public function test_recent_pruning_removes_oldest_records(): void
    {
        config(['heos.enterprise.personalization.recent_max' => 2]);
        $context = $this->context();
        $service = app(RecentActivityService::class);
        $service->record($context, 'page', (string) \Illuminate\Support\Str::uuid7(), 'One');
        $service->record($context, 'page', (string) \Illuminate\Support\Str::uuid7(), 'Two');
        $service->record($context, 'page', (string) \Illuminate\Support\Str::uuid7(), 'Three');
        $this->assertCount(2, $service->list($context));
    }

    public function test_shortcut_uniqueness_updates_existing_entry(): void
    {
        $context = $this->context();
        $service = app(ShortcutService::class);
        $first = $service->create($context, ['shortcut_key' => 'home', 'label' => 'Home']);
        $second = $service->create($context, ['shortcut_key' => 'home', 'label' => 'Home Updated']);
        $this->assertSame($first->publicId, $second->publicId);
        $this->assertSame('Home Updated', $second->label);
        $this->assertCount(1, $service->list($context));
    }

    public function test_onboarding_reset_clears_completed_state(): void
    {
        $context = $this->context();
        $service = app(OnboardingService::class);
        $service->start($context, 'welcome');
        $service->step($context, 'welcome', 'profile');
        $service->complete($context, 'welcome');
        $reset = $service->reset($context, 'welcome');
        $this->assertSame('started', $reset->status);
        $this->assertSame([], $reset->completedSteps);
        $this->assertNull($reset->completedAt);
    }

    public function test_onboarding_completed_is_immutable_until_reset(): void
    {
        $context = $this->context();
        $service = app(OnboardingService::class);
        $service->start($context, 'welcome');
        $service->complete($context, 'welcome');

        $this->expectException(\App\Modules\Sdk\Personalization\Exceptions\OnboardingException::class);
        $service->step($context, 'welcome', 'profile');
    }

    public function test_onboarding_rejects_nonexistent_step(): void
    {
        $context = $this->context();
        $service = app(OnboardingService::class);
        $service->start($context, 'welcome');

        $this->expectException(\App\Modules\Sdk\Personalization\Exceptions\OnboardingException::class);
        $service->step($context, 'welcome', 'missing-step');
    }

    public function test_favorite_remove_is_idempotent(): void
    {
        $context = $this->context();
        $service = app(FavoriteService::class);
        $favorite = $service->add($context, 'page', (string) \Illuminate\Support\Str::uuid7(), 'Page');
        $service->remove($context, $favorite->publicId);
        $service->remove($context, $favorite->publicId);
        $this->assertCount(0, $service->list($context));
    }

    public function test_preference_list_does_not_crash_when_preferences_table_missing(): void
    {
        Schema::drop('personalization_preferences');
        $context = $this->context();
        $this->assertSame([], app(PreferenceService::class)->list($context));
    }

    public function test_profile_list_does_not_crash_when_profiles_table_missing(): void
    {
        Schema::drop('personalization_profiles');
        $context = $this->context();
        $this->assertSame([], app(\App\Services\Personalization\PersonalizationProfileService::class)->list($context));
    }

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