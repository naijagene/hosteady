<?php

namespace Tests\Feature\Seeders;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PlatformBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class PlatformBootstrapSeederTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_seeds_permission_catalog(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);

        $this->assertSame(125, Permission::query()->count());
    }

    public function test_seeds_application_catalog(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);

        $this->assertSame(3, Application::query()->count());
    }

    public function test_does_not_create_organizations(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);

        $this->assertSame(0, Organization::query()->count());
    }

    public function test_does_not_create_users(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);

        $this->assertSame(0, User::query()->count());
    }
}
