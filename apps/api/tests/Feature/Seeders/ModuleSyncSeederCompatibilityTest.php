<?php

namespace Tests\Feature\Seeders;

use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Modules\Core\CoreModule;
use App\Modules\Demo\DemoModule;
use Database\Seeders\ApplicationCatalogSeeder;
use Database\Seeders\ApplicationSettingDefinitionSeeder;
use Database\Seeders\PlatformBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ModuleSyncSeederCompatibilityTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_application_catalog_seeder_still_seeds_core_workspace_demo(): void
    {
        $this->seedApplicationCatalog();

        $this->assertSame(3, Application::query()->count());
        $this->assertEqualsCanonicalizing(
            ['core', 'demo', 'workspace'],
            Application::query()->pluck('key')->all(),
        );
    }

    public function test_application_catalog_seeder_syncs_module_metadata(): void
    {
        $this->seedApplicationCatalog();

        $core = Application::query()->where('key', 'core')->firstOrFail();

        $this->assertSame(CoreModule::MODULE_UUID, $core->module_uuid);
        $this->assertSame(1, $core->manifest_version);
    }

    public function test_application_setting_definition_seeder_still_seeds_demo_definitions(): void
    {
        $this->seed(ApplicationCatalogSeeder::class);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $keys = ApplicationSettingDefinition::query()
            ->where('application_id', $demo->id)
            ->orderBy('sort_order')
            ->pluck('setting_key')
            ->all();

        $this->assertSame(['feature.enabled', 'notification.email', 'secret.token'], $keys);
    }

    public function test_setting_definition_seeder_is_no_op_when_sync_on_seed_enabled(): void
    {
        $this->assertTrue(config('heos.sync.on_seed'));

        $this->seed(ApplicationCatalogSeeder::class);
        $countAfterCatalog = ApplicationSettingDefinition::query()->count();

        $this->seed(ApplicationSettingDefinitionSeeder::class);

        $this->assertSame($countAfterCatalog, ApplicationSettingDefinition::query()->count());
    }

    public function test_platform_bootstrap_seeder_still_passes(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);

        $this->assertSame(3, Application::query()->count());
        $this->assertPermissionCatalogComplete();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $this->assertEqualsCanonicalizing(['notifications', 'reporting'], $demo->capabilities);
        $this->assertSame(DemoModule::MODULE_UUID, $demo->module_uuid);
    }

    public function test_legacy_seeder_path_still_works_when_sync_on_seed_disabled(): void
    {
        config(['heos.sync.on_seed' => false]);

        $this->seed(ApplicationCatalogSeeder::class);

        $this->assertSame(3, Application::query()->count());
        $this->assertNull(Application::query()->where('key', 'core')->value('module_uuid'));
    }
}
