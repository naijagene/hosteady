<?php

namespace Tests\Feature\Services\Audit;

use App\Exceptions\Audit\InvalidAuditCursorException;
use App\Services\Audit\AuditCursorCodec;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditCursorCodecTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_encodes_and_decodes_cursor_for_organization(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'cursor-codec-org']);
        $context = $this->buildTenantContext($user, $result);
        $codec = app(AuditCursorCodec::class);

        $cursor = $codec->encode($context, '01999999-9999-7999-8999-999999999999', '2026-06-23T12:00:00Z');
        $decoded = $codec->decode($context, $cursor);

        $this->assertSame('01999999-9999-7999-8999-999999999999', $decoded['id']);
        $this->assertSame('2026-06-23T12:00:00Z', $decoded['occurred_at']);
        $this->assertStringNotContainsString('organization_id', $cursor);
    }

    public function test_rejects_tampered_cursor(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'cursor-tamper-org']);
        $context = $this->buildTenantContext($user, $result);
        $codec = app(AuditCursorCodec::class);

        $this->expectException(InvalidAuditCursorException::class);

        $codec->decode($context, Crypt::encryptString('invalid-payload'));
    }

    public function test_rejects_cursor_for_different_organization(): void
    {
        $this->seedHeosPermissions();

        $ownerA = $this->createActiveUser();
        $resultA = $this->provisionTestOrganization($ownerA, ['slug' => 'cursor-org-a']);
        $contextA = $this->buildTenantContext($ownerA, $resultA);

        $ownerB = $this->createActiveUser();
        $resultB = $this->provisionTestOrganization($ownerB, ['slug' => 'cursor-org-b']);
        $contextB = $this->buildTenantContext($ownerB, $resultB);

        $codec = app(AuditCursorCodec::class);
        $cursor = $codec->encode($contextA, '01999999-9999-7999-8999-999999999999', '2026-06-23T12:00:00Z');

        $this->expectException(InvalidAuditCursorException::class);

        $codec->decode($contextB, $cursor);
    }

    private function buildTenantContext(
        \App\Models\User $user,
        \App\Services\Organization\Data\ProvisionedOrganizationResult $result,
    ): TenantContext {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
