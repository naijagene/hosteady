<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class MeTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.public_id', $user->public_id)
            ->assertJsonPath('data.email', $user->email);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
