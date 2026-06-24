<?php

namespace App\Services\Invitation;

use App\Enums\InvitationStatus;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Enums\RoleStatus;
use App\Exceptions\Invitation\DuplicatePendingInvitationException;
use App\Exceptions\Invitation\InvitationAlreadyAcceptedException;
use App\Exceptions\Invitation\InvitationException;
use App\Exceptions\Invitation\InvitationExpiredException;
use App\Models\Invitation;
use App\Models\InvitationRole;
use App\Models\Organization;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Invitation\Data\AcceptInvitationData;
use App\Services\Invitation\Data\CreateInvitationData;
use App\Services\Invitation\Data\InvitationAcceptedResult;
use App\Services\Invitation\Data\InvitationCreatedResult;
use App\Support\CodeGenerator;
use App\Support\InvitationToken;
use Illuminate\Support\Facades\DB;

class InvitationService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
        private readonly InvitationToken $invitationToken,
        private readonly \App\Services\Audit\DomainAuditRecorder $domainAuditRecorder,
    ) {
    }

    public function create(CreateInvitationData $data): InvitationCreatedResult
    {
        $organization = Organization::query()
            ->where('public_id', $data->organizationPublicId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $email = $this->normalizeEmail($data->email);
        $roles = $this->resolveInvitationRoles($organization, $data->rolePublicIds);

        $pendingExists = Invitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->where('status', InvitationStatus::Pending)
            ->whereNull('deleted_at')
            ->exists();

        if ($pendingExists) {
            throw new DuplicatePendingInvitationException('A pending invitation already exists for this email.');
        }

        $plainToken = $this->invitationToken->generate();

        return DB::transaction(function () use ($data, $organization, $email, $roles, $plainToken) {
            $invitation = new Invitation([
                'invitation_code' => $this->codeGenerator->invitationCode(),
                'organization_id' => $organization->id,
                'email' => $email,
                'invited_by_user_id' => $data->invitedByUserId,
                'token_hash' => $this->invitationToken->hash($plainToken),
                'status' => InvitationStatus::Pending,
                'expires_at' => now()->addDays($data->expiresInDays),
                'message' => $data->message,
            ]);
            $invitation->applyAuditActor($data->invitedByUserId)->save();

            foreach ($roles as $role) {
                InvitationRole::query()->insert([
                    'invitation_id' => $invitation->id,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'created_by_user_id' => $data->invitedByUserId,
                ]);
            }

            $this->domainAuditRecorder->recordInvitationCreated($invitation);

            return new InvitationCreatedResult(
                invitationPublicId: $invitation->public_id,
                invitationCode: $invitation->invitation_code,
                plainToken: $plainToken,
            );
        });
    }

    public function accept(AcceptInvitationData $data): InvitationAcceptedResult
    {
        $tokenHash = $this->invitationToken->hash($data->plainToken);

        $invitation = Invitation::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('deleted_at')
            ->first();

        if ($invitation === null) {
            throw new InvitationException('Invitation not found.');
        }

        if ($invitation->status === InvitationStatus::Accepted) {
            throw new InvitationAlreadyAcceptedException('Invitation has already been accepted.');
        }

        if ($invitation->status !== InvitationStatus::Pending) {
            throw new InvitationException('Invitation is not pending.');
        }

        if ($invitation->expires_at->isPast()) {
            $invitation->status = InvitationStatus::Expired;
            $invitation->applyAuditActor($data->acceptingUserId)->save();

            $this->domainAuditRecorder->recordInvitationExpired($invitation);

            throw new InvitationExpiredException('Invitation has expired.');
        }

        $user = User::query()
            ->whereKey($data->acceptingUserId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->firstOrFail();

        if ($this->normalizeEmail($user->email) !== $invitation->email) {
            throw new InvitationException('Accepting user email does not match the invitation.');
        }

        $membershipExists = OrganizationMembership::query()
            ->where('organization_id', $invitation->organization_id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($membershipExists) {
            throw new InvitationException('An active membership already exists for this organization.');
        }

        return DB::transaction(function () use ($invitation, $user, $data) {
            $defaultWorkspace = Workspace::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->firstOrFail();

            $membership = new OrganizationMembership([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
                'default_workspace_id' => $defaultWorkspace->id,
                'invited_by_user_id' => $invitation->invited_by_user_id,
                'join_method' => JoinMethod::Invitation,
            ]);
            $membership->applyAuditActor($data->acceptingUserId)->save();

            $invitationRoles = InvitationRole::query()
                ->where('invitation_id', $invitation->id)
                ->get();

            foreach ($invitationRoles as $invitationRole) {
                OrganizationMemberRole::query()->create([
                    'organization_membership_id' => $membership->id,
                    'role_id' => $invitationRole->role_id,
                    'created_at' => now(),
                    'created_by_user_id' => $data->acceptingUserId,
                    'updated_at' => now(),
                    'updated_by_user_id' => $data->acceptingUserId,
                ]);
            }

            $invitation->fill([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
                'accepted_by_user_id' => $user->id,
                'accepted_membership_id' => $membership->id,
            ]);
            $invitation->applyAuditActor($data->acceptingUserId)->save();

            $organization = Organization::query()->findOrFail($invitation->organization_id);

            $this->domainAuditRecorder->recordInvitationAccepted($invitation, $membership);

            return new InvitationAcceptedResult(
                membershipPublicId: $membership->public_id,
                organizationPublicId: $organization->public_id,
                invitationPublicId: $invitation->public_id,
            );
        });
    }

    public function revoke(string $invitationPublicId, int $revokedByUserId): void
    {
        $invitation = Invitation::query()
            ->where('public_id', $invitationPublicId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($invitation->status !== InvitationStatus::Pending) {
            throw new InvitationException('Only pending invitations can be revoked.');
        }

        $invitation->status = InvitationStatus::Revoked;
        $invitation->applyAuditActor($revokedByUserId)->save();

        $this->domainAuditRecorder->recordInvitationRevoked($invitation, $revokedByUserId);
    }

    /**
     * @param  list<string>  $rolePublicIds
     * @return list<Role>
     */
    private function resolveInvitationRoles(Organization $organization, array $rolePublicIds): array
    {
        if ($rolePublicIds === []) {
            throw new InvitationException('At least one role must be assigned to an invitation.');
        }

        $roles = Role::query()
            ->whereIn('public_id', $rolePublicIds)
            ->whereNull('deleted_at')
            ->get();

        if ($roles->count() !== count($rolePublicIds)) {
            throw new InvitationException('One or more invitation roles could not be found.');
        }

        foreach ($roles as $role) {
            if ($role->organization_id !== $organization->id) {
                throw new InvitationException('Role does not belong to the target organization.');
            }

            if ($role->organization_id === null) {
                throw new InvitationException('Platform roles cannot be assigned through invitations.');
            }

            if ($role->status !== RoleStatus::Active) {
                throw new InvitationException('Only active roles can be assigned to invitations.');
            }

            if ($role->key === 'owner') {
                $this->domainAuditRecorder->recordRoleEscalationAttempt($organization, $role->key);

                throw new InvitationException('The owner role cannot be assigned through invitations.');
            }
        }

        return $roles->all();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
