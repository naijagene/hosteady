<?php

namespace Tests\Feature\Seeders;

use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use Database\Seeders\ApplicationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationSettingDefinitionSeederTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_seeds_demo_manifest_columns(): void
    {
        $this->seed(ApplicationCatalogSeeder::class);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertEqualsCanonicalizing(['notifications', 'reporting'], $demo->capabilities);
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $demo->dependencies);
    }

    public function test_seeds_demo_setting_definitions(): void
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

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ApplicationCatalogSeeder::class);
        $this->seed(ApplicationCatalogSeeder::class);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertSame(3, ApplicationSettingDefinition::query()->where('application_id', $demo->id)->count());
    }
}
