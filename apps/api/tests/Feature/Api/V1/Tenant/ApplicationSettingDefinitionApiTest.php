<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationSettingDefinitionApiTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_lists_demo_application_setting_definitions(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'definitions-api-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/'.$demo->public_id.'/settings/definitions');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.setting_key', 'feature.enabled')
            ->assertJsonPath('data.0.type', 'boolean')
            ->assertJsonPath('data.0.default_value', false)
            ->assertJsonPath('data.1.setting_key', 'notification.email')
            ->assertJsonPath('data.2.setting_key', 'secret.token')
            ->assertJsonPath('data.2.is_sensitive', true);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_returns_empty_collection_for_application_without_definitions(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'definitions-api-core-org']);
        $core = Application::query()->where('key', 'core')->firstOrFail();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/'.$core->public_id.'/settings/definitions')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_not_found_for_unknown_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'definitions-api-unknown-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/01999999-9999-7999-8999-999999999999/settings/definitions')
            ->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'definitions-api-auth-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/'.$demo->public_id.'/settings/definitions')
            ->assertUnauthorized();
    }

    public function test_requires_organization_header(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $this->provisionTestOrganization($user, ['slug' => 'definitions-api-header-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/tenant/applications/'.$demo->public_id.'/settings/definitions')
            ->assertStatus(422);
    }

    public function test_includes_validation_rules_in_response(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'definitions-api-rules-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications/'.$demo->public_id.'/settings/definitions')
            ->assertOk()
            ->assertJsonPath('data.1.validation_rules.pattern', '^[^@]+@[^@]+\.[^@]+$')
            ->assertJsonPath('data.2.validation_rules.min_length', 8);
    }
}
