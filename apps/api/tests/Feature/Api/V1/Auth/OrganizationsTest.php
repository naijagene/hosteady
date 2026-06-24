<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class OrganizationsTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_lists_active_organizations_for_member(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'member-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/auth/organizations');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $result->organizationPublicId)
            ->assertJsonPath('data.0.membership.public_id', $result->membershipPublicId)
            ->assertJsonPath('data.0.membership.default_workspace_public_id', $result->workspacePublicId);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_excludes_organizations_without_active_membership(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $this->provisionTestOrganization($owner, ['slug' => 'owner-org']);

        $outsider = $this->createActiveUser();
        $token = $this->issueToken($outsider);

        $this->withBearerToken($token)
            ->getJson('/api/v1/auth/organizations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_excludes_non_active_organizations(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'suspended-org']);
        $organization = $this->findProvisionedOrganization($result);
        $organization->status = OrganizationStatus::Suspended;
        $organization->save();

        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/auth/organizations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_empty_list_when_user_has_no_memberships(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/auth/organizations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/organizations')
            ->assertUnauthorized();
    }
}
