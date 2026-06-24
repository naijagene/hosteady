<?php

namespace Tests\Feature\Services\Invitation;

use App\Enums\InvitationStatus;
use App\Enums\JoinMethod;
use App\Exceptions\Invitation\DuplicatePendingInvitationException;
use App\Exceptions\Invitation\InvitationException;
use App\Exceptions\Invitation\InvitationExpiredException;
use App\Models\Invitation;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Invitation\Data\AcceptInvitationData;
use App\Services\Invitation\Data\CreateInvitationData;
use App\Services\Invitation\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class InvitationServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHeosPermissions();
        $this->service = app(InvitationService::class);
    }

    public function test_creates_pending_invitation(): void
    {
        [$owner, $organization, $memberRole] = $this->provisionOrganizationWithMemberRole();

        $result = $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));

        $invitation = Invitation::query()
            ->where('public_id', $result->invitationPublicId)
            ->firstOrFail();

        $this->assertSame(InvitationStatus::Pending, $invitation->status);
        $this->assertSame('INV-000001', $result->invitationCode);
        $this->assertSame('invitee@example.com', $invitation->email);
        $this->assertNotSame($result->plainToken, $invitation->token_hash);
    }

    public function test_returns_plain_token_once(): void
    {
        [$owner, $organization, $memberRole] = $this->provisionOrganizationWithMemberRole();

        $result = $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));

        $this->assertNotEmpty($result->plainToken);
        $this->assertSame(64, strlen($result->plainToken));
    }

    public function test_throws_on_duplicate_pending_invitation(): void
    {
        [$owner, $organization, $memberRole] = $this->provisionOrganizationWithMemberRole();

        $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));

        $this->expectException(DuplicatePendingInvitationException::class);

        $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));
    }

    public function test_throws_when_owner_role_requested(): void
    {
        [$owner, $organization] = $this->provisionOrganizationWithMemberRole();

        $ownerRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'owner')
            ->firstOrFail();

        $this->expectException(InvitationException::class);
        $this->expectExceptionMessage('The owner role cannot be assigned through invitations.');

        $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$ownerRole->public_id],
        ));
    }

    public function test_accepts_invitation_happy_path(): void
    {
        [$owner, $organization, $memberRole, $created] = $this->createPendingInvitation();

        $invitee = User::factory()->create([
            'email' => 'invitee@example.com',
            'status' => 'active',
        ]);

        $result = $this->service->accept(new AcceptInvitationData(
            plainToken: $created->plainToken,
            acceptingUserId: $invitee->id,
        ));

        $membership = OrganizationMembership::query()
            ->where('public_id', $result->membershipPublicId)
            ->firstOrFail();

        $this->assertSame($invitee->id, $membership->user_id);
        $this->assertSame(JoinMethod::Invitation, $membership->join_method);
        $this->assertSame($owner->id, $membership->invited_by_user_id);
    }

    public function test_copies_roles_to_membership(): void
    {
        [$owner, $organization, $memberRole, $created] = $this->createPendingInvitation();

        $invitee = User::factory()->create([
            'email' => 'invitee@example.com',
            'status' => 'active',
        ]);

        $result = $this->service->accept(new AcceptInvitationData(
            plainToken: $created->plainToken,
            acceptingUserId: $invitee->id,
        ));

        $membership = OrganizationMembership::query()
            ->where('public_id', $result->membershipPublicId)
            ->firstOrFail();

        $this->assertTrue(
            OrganizationMemberRole::query()
                ->where('organization_membership_id', $membership->id)
                ->where('role_id', $memberRole->id)
                ->exists()
        );
    }

    public function test_sets_accepted_membership_id(): void
    {
        [, , , $created] = $this->createPendingInvitation();

        $invitee = User::factory()->create([
            'email' => 'invitee@example.com',
            'status' => 'active',
        ]);

        $result = $this->service->accept(new AcceptInvitationData(
            plainToken: $created->plainToken,
            acceptingUserId: $invitee->id,
        ));

        $invitation = Invitation::query()
            ->where('public_id', $result->invitationPublicId)
            ->firstOrFail();

        $this->assertSame(InvitationStatus::Accepted, $invitation->status);
        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($invitee->id, $invitation->accepted_by_user_id);
        $this->assertSame($result->membershipPublicId, $invitation->acceptedMembership->public_id);
    }

    public function test_throws_when_token_expired(): void
    {
        [$owner, $organization, $memberRole] = $this->provisionOrganizationWithMemberRole();

        $created = $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));

        Invitation::query()
            ->where('public_id', $created->invitationPublicId)
            ->update(['expires_at' => now()->subMinute()]);

        $invitee = User::factory()->create([
            'email' => 'invitee@example.com',
            'status' => 'active',
        ]);

        $this->expectException(InvitationExpiredException::class);

        try {
            $this->service->accept(new AcceptInvitationData(
                plainToken: $created->plainToken,
                acceptingUserId: $invitee->id,
            ));
        } finally {
            $invitation = Invitation::query()
                ->where('public_id', $created->invitationPublicId)
                ->firstOrFail();

            $this->assertSame(InvitationStatus::Expired, $invitation->status);
        }
    }

    public function test_throws_when_email_mismatch(): void
    {
        [, , , $created] = $this->createPendingInvitation();

        $otherUser = User::factory()->create([
            'email' => 'someone-else@example.com',
            'status' => 'active',
        ]);

        $this->expectException(InvitationException::class);
        $this->expectExceptionMessage('Accepting user email does not match the invitation.');

        $this->service->accept(new AcceptInvitationData(
            plainToken: $created->plainToken,
            acceptingUserId: $otherUser->id,
        ));
    }

    public function test_revoke_sets_status_revoked(): void
    {
        [$owner, , , $created] = $this->createPendingInvitation();

        $this->service->revoke($created->invitationPublicId, $owner->id);

        $invitation = Invitation::query()
            ->where('public_id', $created->invitationPublicId)
            ->firstOrFail();

        $this->assertSame(InvitationStatus::Revoked, $invitation->status);
    }

    /**
     * @return array{0: User, 1: \App\Models\Organization, 2: Role}
     */
    private function provisionOrganizationWithMemberRole(): array
    {
        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invite-org-'.uniqid()]);
        $organization = $this->findProvisionedOrganization($result);

        $memberRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        return [$owner, $organization, $memberRole];
    }

    /**
     * @return array{0: User, 1: \App\Models\Organization, 2: Role, 3: \App\Services\Invitation\Data\InvitationCreatedResult}
     */
    private function createPendingInvitation(): array
    {
        [$owner, $organization, $memberRole] = $this->provisionOrganizationWithMemberRole();

        $created = $this->service->create(new CreateInvitationData(
            organizationPublicId: $organization->public_id,
            invitedByUserId: $owner->id,
            email: 'invitee@example.com',
            rolePublicIds: [$memberRole->public_id],
        ));

        return [$owner, $organization, $memberRole, $created];
    }
}
