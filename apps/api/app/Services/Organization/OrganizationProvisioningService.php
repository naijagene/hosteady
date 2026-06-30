<?php

namespace App\Services\Organization;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\WorkspaceStatus;
use App\Exceptions\Organization\DuplicateOrganizationSlugException;
use App\Exceptions\Organization\OrganizationProvisioningException;
use App\Models\Organization;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Authorization\SystemRoleProvisioner;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\Data\ProvisionedOrganizationResult;
use App\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class OrganizationProvisioningService
{
    private const EXPECTED_PERMISSION_COUNT = 134;

    /**
     * @var list<string>
     */
    private const ALLOWED_PLAN_TIERS = ['free', 'starter', 'business', 'enterprise'];

    public function __construct(
        private readonly SystemRoleProvisioner $systemRoleProvisioner,
        private readonly CodeGenerator $codeGenerator,
        private readonly \App\Services\Audit\DomainAuditRecorder $domainAuditRecorder,
        private readonly \App\Services\WorkspaceApplication\WorkspaceApplicationBootstrapService $workspaceApplicationBootstrapService,
    ) {
    }

    public function provision(CreateOrganizationData $data): ProvisionedOrganizationResult
    {
        $this->validateInput($data);

        return DB::transaction(function () use ($data) {
            $organization = new Organization([
                'name' => $data->name,
                'slug' => $data->slug,
                'status' => OrganizationStatus::Provisioning,
                'timezone' => $data->timezone,
                'locale' => $data->locale,
                'plan_tier' => $data->planTier,
                'organization_code' => null,
                'owner_user_id' => null,
            ]);
            $organization->applyAuditActor($data->creatorUserId)->save();

            $workspace = new Workspace([
                'organization_id' => $organization->id,
                'name' => 'Default',
                'slug' => 'default',
                'is_default' => true,
                'status' => WorkspaceStatus::Active,
            ]);
            $workspace->applyAuditActor($data->creatorUserId)->save();

            $this->systemRoleProvisioner->provisionForOrganization($organization, $data->creatorUserId);

            $membership = new OrganizationMembership([
                'organization_id' => $organization->id,
                'user_id' => $data->creatorUserId,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
                'default_workspace_id' => $workspace->id,
                'invited_by_user_id' => null,
                'join_method' => JoinMethod::System,
            ]);
            $membership->applyAuditActor($data->creatorUserId)->save();

            $ownerRole = Role::query()
                ->where('organization_id', $organization->id)
                ->where('key', 'owner')
                ->where('is_system', true)
                ->whereNull('deleted_at')
                ->firstOrFail();

            OrganizationMemberRole::query()->create([
                'organization_membership_id' => $membership->id,
                'role_id' => $ownerRole->id,
                'created_at' => now(),
                'created_by_user_id' => $data->creatorUserId,
                'updated_at' => now(),
                'updated_by_user_id' => $data->creatorUserId,
            ]);

            $organizationCode = $data->organizationCode ?? $this->codeGenerator->organizationCode();

            $organization->fill([
                'owner_user_id' => $data->creatorUserId,
                'organization_code' => $organizationCode,
                'status' => OrganizationStatus::Active,
            ]);
            $organization->applyAuditActor($data->creatorUserId)->save();

            $this->domainAuditRecorder->recordOrganizationStatusChanged(
                $organization,
                OrganizationStatus::Provisioning,
                OrganizationStatus::Active,
                $data->creatorUserId,
            );
            $this->domainAuditRecorder->recordOrganizationCreated($organization);
            $this->domainAuditRecorder->recordWorkspaceCreated($workspace, $data->creatorUserId);
            $this->domainAuditRecorder->recordMembershipCreated($membership, $data->creatorUserId);

            $creator = User::query()->findOrFail($data->creatorUserId);

            $this->workspaceApplicationBootstrapService->bootstrapDefaultWorkspace(
                $organization,
                $workspace,
                $membership,
                $creator,
                $data->creatorUserId,
            );

            return new ProvisionedOrganizationResult(
                organizationPublicId: $organization->public_id,
                workspacePublicId: $workspace->public_id,
                membershipPublicId: $membership->public_id,
                organizationCode: $organizationCode,
            );
        });
    }

    private function validateInput(CreateOrganizationData $data): void
    {
        if (Permission::query()->count() !== self::EXPECTED_PERMISSION_COUNT) {
            throw new OrganizationProvisioningException('Permission catalog must be seeded before provisioning.');
        }

        if (! in_array($data->planTier, self::ALLOWED_PLAN_TIERS, true)) {
            throw new OrganizationProvisioningException('Invalid plan tier.');
        }

        $creatorExists = User::query()
            ->whereKey($data->creatorUserId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->exists();

        if (! $creatorExists) {
            throw new OrganizationProvisioningException('Creator user is invalid or inactive.');
        }

        $slugExists = Organization::query()
            ->where('slug', $data->slug)
            ->whereNull('deleted_at')
            ->exists();

        if ($slugExists) {
            throw new DuplicateOrganizationSlugException('Organization slug is already in use.');
        }
    }
}
