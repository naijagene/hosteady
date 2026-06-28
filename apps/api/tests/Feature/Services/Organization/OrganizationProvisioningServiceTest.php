<?php

namespace Tests\Feature\Services\Organization;

use App\Enums\JoinMethod;
use App\Enums\OrganizationStatus;
use App\Enums\WorkspaceStatus;
use App\Exceptions\Organization\DuplicateOrganizationSlugException;
use App\Exceptions\Organization\OrganizationProvisioningException;
use App\Models\Organization;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\OrganizationProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class OrganizationProvisioningServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private OrganizationProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrganizationProvisioningService::class);
    }

    public function test_provisions_organization_happy_path(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $result = $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $organization = Organization::query()
            ->where('public_id', $result->organizationPublicId)
            ->firstOrFail();

        $this->assertSame(OrganizationStatus::Active, $organization->status);
        $this->assertSame('ORG-000001', $result->organizationCode);
        $this->assertSame('ORG-000001', $organization->organization_code);
        $this->assertNotEmpty($result->workspacePublicId);
        $this->assertNotEmpty($result->membershipPublicId);
    }

    public function test_creates_default_workspace(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $result = $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $organization = $this->findProvisionedOrganization($result);

        $workspace = Workspace::query()
            ->where('public_id', $result->workspacePublicId)
            ->firstOrFail();

        $this->assertSame('default', $workspace->slug);
        $this->assertTrue($workspace->is_default);
        $this->assertSame(WorkspaceStatus::Active, $workspace->status);
        $this->assertSame($organization->id, $workspace->organization_id);
    }

    public function test_creates_five_system_roles(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $result = $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $organization = $this->findProvisionedOrganization($result);

        $this->assertSame(5, Role::query()
            ->where('organization_id', $organization->id)
            ->where('is_system', true)
            ->whereNull('deleted_at')
            ->count());
    }

    public function test_assigns_owner_membership_and_role(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $result = $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $membership = OrganizationMembership::query()
            ->where('public_id', $result->membershipPublicId)
            ->firstOrFail();

        $this->assertSame($creator->id, $membership->user_id);
        $this->assertSame(JoinMethod::System, $membership->join_method);
        $this->assertNull($membership->invited_by_user_id);

        $ownerRole = Role::query()
            ->where('organization_id', $membership->organization_id)
            ->where('key', 'owner')
            ->firstOrFail();

        $this->assertTrue(
            OrganizationMemberRole::query()
                ->where('organization_membership_id', $membership->id)
                ->where('role_id', $ownerRole->id)
                ->exists()
        );
    }

    public function test_owner_role_has_all_twenty_two_permissions(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $result = $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $organization = $this->findProvisionedOrganization($result);

        $ownerRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'owner')
            ->firstOrFail();

        $this->assertSame(80, DB::table('role_permissions')
            ->where('role_id', $ownerRole->id)
            ->count());
    }

    public function test_throws_when_slug_already_exists(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));

        $this->expectException(DuplicateOrganizationSlugException::class);

        $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Duplicate',
            slug: 'acme-corp',
        ));
    }

    public function test_throws_when_permission_catalog_not_seeded(): void
    {
        $creator = User::factory()->create(['status' => 'active']);

        $this->expectException(OrganizationProvisioningException::class);
        $this->expectExceptionMessage('Permission catalog must be seeded before provisioning.');

        app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));
    }

    public function test_throws_when_creator_is_inactive(): void
    {
        $this->seedHeosPermissions();

        $creator = User::factory()->create(['status' => 'inactive']);

        $this->expectException(OrganizationProvisioningException::class);
        $this->expectExceptionMessage('Creator user is invalid or inactive.');

        $this->service->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Acme Corp',
            slug: 'acme-corp',
        ));
    }
}
