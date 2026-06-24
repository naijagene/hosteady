<?php

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use Database\Seeders\PermissionCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class PermissionCatalogSeederTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_seeds_exactly_seventeen_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertPermissionCatalogComplete();
    }

    public function test_seeds_all_expected_permission_keys(): void
    {
        $this->seedHeosPermissions();

        $keys = Permission::query()->pluck('key')->all();

        $this->assertEqualsCanonicalizing(
            $this->expectedPermissionKeys(),
            $keys
        );
    }

    public function test_is_idempotent_on_second_run(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $this->seed(PermissionCatalogSeeder::class);

        $this->assertPermissionCatalogComplete();

        $permission = Permission::query()->where('key', 'organization.read')->firstOrFail();

        $this->assertSame('Read Organization', $permission->name);
        $this->assertSame('organization', $permission->domain);
    }

    public function test_assigns_public_id_to_new_permissions(): void
    {
        $this->seedHeosPermissions();

        Permission::query()->each(function (Permission $permission) {
            $this->assertNotNull($permission->public_id);
            $this->assertNotSame('', $permission->public_id);
        });
    }
}
