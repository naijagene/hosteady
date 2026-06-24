<?php

namespace Tests\Feature\Api\V1\Tenant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationCatalogTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_returns_tenant_catalog_for_member(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'tenant-catalog-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/catalog');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_requires_organization_header(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/tenant/applications/catalog')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The X-HEOS-Organization-Id header is required.');
    }
}
