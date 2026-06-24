<?php

namespace Tests\Feature\Support;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationStatus;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class CodeGeneratorTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private CodeGenerator $codeGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codeGenerator = app(CodeGenerator::class);
    }

    public function test_generates_org_000001_for_first_organization(): void
    {
        $this->assertSame('ORG-000001', $this->codeGenerator->organizationCode());
    }

    public function test_increments_organization_code_sequentially(): void
    {
        $user = $this->createActiveUser();

        $this->createOrganizationWithCode('ORG-000001', $user);

        $this->assertSame('ORG-000002', $this->codeGenerator->organizationCode());
    }

    public function test_generates_inv_000001_for_first_invitation(): void
    {
        $this->assertSame('INV-000001', $this->codeGenerator->invitationCode());
    }

    public function test_uses_six_digit_zero_padding(): void
    {
        $user = $this->createActiveUser();

        $this->createOrganizationWithCode('ORG-000009', $user);

        $this->assertSame('ORG-000010', $this->codeGenerator->organizationCode());
    }

    public function test_invitation_and_organization_sequences_are_independent(): void
    {
        $user = $this->createActiveUser();
        $organization = $this->createOrganizationWithCode('ORG-000001', $user);

        $this->createInvitationWithCode('INV-000001', $organization, $user);

        $this->assertSame('ORG-000002', $this->codeGenerator->organizationCode());
        $this->assertSame('INV-000002', $this->codeGenerator->invitationCode());
    }

    private function createOrganizationWithCode(string $code, User $user): Organization
    {
        return Organization::query()->create([
            'name' => 'Code Generator Org '.$code,
            'slug' => 'org-'.Str::lower(Str::random(8)),
            'status' => OrganizationStatus::Active,
            'timezone' => 'UTC',
            'locale' => 'en',
            'plan_tier' => 'free',
            'organization_code' => $code,
            'owner_user_id' => $user->id,
        ]);
    }

    private function createInvitationWithCode(string $code, Organization $organization, User $user): Invitation
    {
        return Invitation::query()->create([
            'invitation_code' => $code,
            'organization_id' => $organization->id,
            'email' => 'invite-'.Str::random(8).'@example.com',
            'invited_by_user_id' => $user->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);
    }
}
