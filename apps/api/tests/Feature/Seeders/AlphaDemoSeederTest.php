<?php

namespace Tests\Feature\Seeders;

use App\Models\Organization;
use App\Models\PersonalizationPreference;
use App\Models\PersonalizationProfile;
use App\Models\ThemeDefinition;
use App\Models\UiPage;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\AlphaDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AlphaDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('ALPHA_DEMO_PASSWORD=alpha-test-placeholder');
        $_ENV['ALPHA_DEMO_PASSWORD'] = 'alpha-test-placeholder';
    }

    public function test_provisions_moondew_group_alpha_tenant(): void
    {
        $this->seed(AlphaDemoSeeder::class);

        $organization = Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->first();
        $this->assertNotNull($organization);
        $this->assertSame(AlphaDemoSeeder::ORGANIZATION_NAME, $organization->name);

        $workspace = Workspace::query()
            ->where('organization_id', $organization->id)
            ->where('slug', AlphaDemoSeeder::WORKSPACE_SLUG)
            ->first();
        $this->assertNotNull($workspace);
        $this->assertSame(AlphaDemoSeeder::WORKSPACE_NAME, $workspace->name);

        $admin = User::query()->where('email', 'bigjyde@alpha.demo.local')->first();
        $this->assertNotNull($admin);
        $this->assertSame('BIGJYDE', $admin->display_name);
    }

    public function test_is_idempotent_for_base_tenant(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(AlphaDemoSeeder::class);

        $this->assertSame(1, Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->count());
        $this->assertSame(1, User::query()->where('email', 'bigjyde@alpha.demo.local')->count());
    }

    public function test_completes_metadata_after_re_run(): void
    {
        $this->seed(AlphaDemoSeeder::class);
        $this->seed(AlphaDemoSeeder::class);

        if (Schema::hasTable('theme_definitions')) {
            $this->assertGreaterThan(
                0,
                ThemeDefinition::query()->where('module_key', AlphaDemoSeeder::MODULE_KEY)->count(),
            );
        }

        if (Schema::hasTable('personalization_profiles')) {
            $this->assertGreaterThan(0, PersonalizationProfile::query()->count());
        }

        if (Schema::hasTable('personalization_preferences')) {
            $this->assertGreaterThan(0, PersonalizationPreference::query()->count());
        }

        if (Schema::hasTable('ui_pages')) {
            $this->assertGreaterThan(
                0,
                UiPage::query()->where('module_key', AlphaDemoSeeder::MODULE_KEY)->count(),
            );
        }
    }

    public function test_does_not_hardcode_password(): void
    {
        $this->seed(AlphaDemoSeeder::class);

        $admin = User::query()->where('email', 'bigjyde@alpha.demo.local')->firstOrFail();
        $this->assertNotSame('alpha-test-placeholder', $admin->password);
        $this->assertTrue(Hash::check('alpha-test-placeholder', $admin->password));
    }

    public function test_skips_when_password_env_missing(): void
    {
        putenv('ALPHA_DEMO_PASSWORD=');
        unset($_ENV['ALPHA_DEMO_PASSWORD']);

        $this->seed(AlphaDemoSeeder::class);

        $this->assertSame(0, Organization::query()->where('slug', AlphaDemoSeeder::ORGANIZATION_SLUG)->count());
    }
}
