<?php

namespace Tests\Feature\Seeders;

use App\Models\Application;
use Database\Seeders\ApplicationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationCatalogSeederTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_seeds_four_catalog_applications(): void
    {
        $this->seedApplicationCatalog();

        $this->assertSame(4, Application::query()->count());
    }

    public function test_seeds_expected_application_keys(): void
    {
        $this->seedApplicationCatalog();

        $keys = Application::query()->pluck('key')->all();

        $this->assertEqualsCanonicalizing(['core', 'demo', 'hosteady-admin', 'workspace'], $keys);
    }

    public function test_marks_core_and_non_core_applications_correctly(): void
    {
        $this->seedApplicationCatalog();

        $this->assertTrue(Application::query()->where('key', 'core')->firstOrFail()->is_core);
        $this->assertTrue(Application::query()->where('key', 'workspace')->firstOrFail()->is_core);
        $this->assertFalse(Application::query()->where('key', 'demo')->firstOrFail()->is_core);
    }

    public function test_seeds_demo_application_metadata(): void
    {
        $this->seedApplicationCatalog();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertSame('Demo Application', $demo->name);
        $this->assertSame('1.0.0', $demo->version);
        $this->assertSame('platform', $demo->category);
        $this->assertNull($demo->icon);
    }

    public function test_is_idempotent_on_second_run(): void
    {
        $this->seed(ApplicationCatalogSeeder::class);
        $this->seed(ApplicationCatalogSeeder::class);

        $this->assertSame(4, Application::query()->count());

        $core = Application::query()->where('key', 'core')->firstOrFail();

        $this->assertSame('HEOS Core', $core->name);
        $this->assertNotNull($core->public_id);
    }

    public function test_assigns_public_id_to_new_applications(): void
    {
        $this->seedApplicationCatalog();

        Application::query()->each(function (Application $application) {
            $this->assertNotNull($application->public_id);
            $this->assertNotSame('', $application->public_id);
        });
    }
}
