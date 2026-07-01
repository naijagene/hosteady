<?php

namespace Tests\Feature\Api\V1;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationCatalogTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->seedApplicationCatalog();

        $this->getJson('/api/v1/applications/catalog')
            ->assertUnauthorized();
    }

    public function test_returns_platform_catalog_without_tenant_header(): void
    {
        $this->seedApplicationCatalog();

        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/applications/catalog');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'public_id',
                        'key',
                        'name',
                        'description',
                        'version',
                        'status',
                        'is_core',
                        'icon',
                        'category',
                    ],
                ],
            ]);

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['core', 'demo', 'hosteady-admin', 'workspace'], $keys);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $response->assertJsonFragment([
            'public_id' => $demo->public_id,
            'key' => 'demo',
            'name' => 'Demo Application',
            'is_core' => false,
        ]);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }
}
