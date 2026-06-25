<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Enums\InvitationStatus;
use App\Enums\OrganizationApplicationStatus;
use App\Enums\OrganizationStatus;
use App\Exceptions\Tenant\TenantContextException;
use App\Http\Middleware\ResolveTenantContext;
use App\Models\Application;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Audit\Data\AuditEventData;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class DomainAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $recorder,
    ) {
    }

    public function safeRecord(AuditEventData $event): void
    {
        try {
            $this->recorder->record($event);
        } catch (\Throwable) {
            // Audit failures must never break business workflows.
        }
    }

    public function recordLoginSucceeded(User $user): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::AuthLoginSucceeded,
            summary: sprintf('User %s logged in', $user->display_name ?? $user->name),
            scope: AuditScope::Platform,
            entityType: AuditEntityType::User,
            entityPublicId: $user->public_id,
            entityLabel: $user->display_name ?? $user->name,
            afterState: self::snapshot(AuditEntityType::User, [
                'status' => $user->status,
                'email' => $user->email,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $user->id,
            retentionClass: AuditRetentionClass::Standard,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordLoginFailed(?User $user, string $email): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::AuthLoginFailed,
            summary: 'Login attempt failed',
            scope: AuditScope::Platform,
            entityType: $user !== null ? AuditEntityType::User : null,
            entityPublicId: $user?->public_id,
            entityLabel: $user?->display_name ?? $user?->name,
            metadata: [
                'email_hash' => hash('sha256', strtolower(trim($email))),
            ],
            actorType: $user !== null ? AuditActorType::User : AuditActorType::Platform,
            actorUserId: $user?->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordLogoutSucceeded(User $user): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::AuthLogoutSucceeded,
            summary: sprintf('User %s logged out', $user->display_name ?? $user->name),
            scope: AuditScope::Platform,
            entityType: AuditEntityType::User,
            entityPublicId: $user->public_id,
            entityLabel: $user->display_name ?? $user->name,
            actorType: AuditActorType::User,
            actorUserId: $user->id,
            retentionClass: AuditRetentionClass::Standard,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordInvalidToken(): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::SecurityInvalidToken,
            summary: 'Invalid or missing authentication token',
            scope: AuditScope::Platform,
            actorType: AuditActorType::Platform,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordPermissionDenied(Request $request, AuthorizationException $exception): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->safeRecord(new AuditEventData(
            action: AuditAction::SecurityPermissionDenied,
            summary: 'Permission denied',
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::Organization,
            entityPublicId: $context->organization->public_id,
            entityLabel: $context->organization->name,
            metadata: [
                'message' => $exception->getMessage(),
            ],
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordTenantRejected(Request $request, TenantContextException $exception): void
    {
        $organizationPublicId = $request->header(ResolveTenantContext::ORGANIZATION_HEADER);
        $organizationId = null;

        if (is_string($organizationPublicId) && $organizationPublicId !== '') {
            $organizationId = Organization::query()
                ->where('public_id', $organizationPublicId)
                ->value('id');
        }

        $this->safeRecord(new AuditEventData(
            action: AuditAction::SecurityTenantRejected,
            summary: 'Tenant context rejected',
            scope: $organizationId !== null ? AuditScope::Organization : AuditScope::Platform,
            organizationId: $organizationId,
            entityType: AuditEntityType::Organization,
            entityPublicId: is_string($organizationPublicId) ? $organizationPublicId : null,
            metadata: [
                'message' => $exception->getMessage(),
                'status_code' => $exception->statusCode,
            ],
            actorType: AuditActorType::User,
            actorUserId: $request->user()?->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordRoleEscalationAttempt(Organization $organization, string $roleKey): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::SecurityRoleEscalationAttempt,
            summary: sprintf('Role escalation attempt blocked for %s', $roleKey),
            scope: AuditScope::Organization,
            organizationId: $organization->id,
            entityType: AuditEntityType::Role,
            entityLabel: $roleKey,
            metadata: [
                'role_key' => $roleKey,
            ],
            retentionClass: AuditRetentionClass::Standard,
            severity: AuditSeverity::Critical,
        ));
    }

    public function recordOrganizationCreated(Organization $organization): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::OrganizationCreated,
            summary: sprintf('Organization %s created', $organization->name),
            scope: AuditScope::Organization,
            organizationId: $organization->id,
            entityType: AuditEntityType::Organization,
            entityPublicId: $organization->public_id,
            entityLabel: $organization->name,
            afterState: self::snapshot(AuditEntityType::Organization, [
                'name' => $organization->name,
                'slug' => $organization->slug,
                'status' => $organization->status->value,
                'timezone' => $organization->timezone,
                'locale' => $organization->locale,
                'plan_tier' => $organization->plan_tier,
            ]),
            actorUserId: $organization->owner_user_id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordOrganizationStatusChanged(
        Organization $organization,
        OrganizationStatus $from,
        OrganizationStatus $to,
        int $actorUserId,
    ): void {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::OrganizationStatusChanged,
            summary: sprintf('Organization status changed to %s', $to->value),
            scope: AuditScope::Organization,
            organizationId: $organization->id,
            entityType: AuditEntityType::Organization,
            entityPublicId: $organization->public_id,
            entityLabel: $organization->name,
            beforeState: self::diff(AuditEntityType::Organization, 'status', $from->value, $to->value),
            afterState: self::snapshot(AuditEntityType::Organization, [
                'status' => $to->value,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $actorUserId,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordWorkspaceCreated(Workspace $workspace, int $actorUserId): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::WorkspaceCreated,
            summary: sprintf('Workspace %s created', $workspace->name),
            scope: AuditScope::Organization,
            organizationId: $workspace->organization_id,
            workspaceId: $workspace->id,
            entityType: AuditEntityType::Workspace,
            entityPublicId: $workspace->public_id,
            entityLabel: $workspace->name,
            afterState: self::snapshot(AuditEntityType::Workspace, [
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'status' => $workspace->status->value,
                'is_default' => $workspace->is_default,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $actorUserId,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordMembershipCreated(OrganizationMembership $membership, int $actorUserId): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::MembershipCreated,
            summary: 'Organization membership created',
            scope: AuditScope::Organization,
            organizationId: $membership->organization_id,
            workspaceId: $membership->default_workspace_id,
            entityType: AuditEntityType::OrganizationMembership,
            entityPublicId: $membership->public_id,
            afterState: self::snapshot(AuditEntityType::OrganizationMembership, [
                'status' => $membership->status->value,
                'join_method' => $membership->join_method->value,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $actorUserId,
            actorMembershipId: $membership->id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordInvitationCreated(Invitation $invitation): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::InvitationCreated,
            summary: sprintf('Invitation created for %s', $invitation->email),
            scope: AuditScope::Organization,
            organizationId: $invitation->organization_id,
            entityType: AuditEntityType::Invitation,
            entityPublicId: $invitation->public_id,
            entityLabel: $invitation->email,
            afterState: self::snapshot(AuditEntityType::Invitation, [
                'email' => $invitation->email,
                'status' => $invitation->status->value,
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ]),
            actorType: AuditActorType::User,
            actorUserId: $invitation->invited_by_user_id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordInvitationAccepted(Invitation $invitation, OrganizationMembership $membership): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::InvitationAccepted,
            summary: sprintf('Invitation accepted for %s', $invitation->email),
            scope: AuditScope::Organization,
            organizationId: $invitation->organization_id,
            entityType: AuditEntityType::Invitation,
            entityPublicId: $invitation->public_id,
            entityLabel: $invitation->email,
            beforeState: self::diff(AuditEntityType::Invitation, 'status', InvitationStatus::Pending->value, InvitationStatus::Accepted->value),
            afterState: self::snapshot(AuditEntityType::Invitation, [
                'status' => $invitation->status->value,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $invitation->accepted_by_user_id,
            actorMembershipId: $membership->id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordInvitationRevoked(Invitation $invitation, int $revokedByUserId): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::InvitationRevoked,
            summary: sprintf('Invitation revoked for %s', $invitation->email),
            scope: AuditScope::Organization,
            organizationId: $invitation->organization_id,
            entityType: AuditEntityType::Invitation,
            entityPublicId: $invitation->public_id,
            entityLabel: $invitation->email,
            beforeState: self::diff(AuditEntityType::Invitation, 'status', InvitationStatus::Pending->value, InvitationStatus::Revoked->value),
            afterState: self::snapshot(AuditEntityType::Invitation, [
                'status' => $invitation->status->value,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $revokedByUserId,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordInvitationExpired(Invitation $invitation): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::InvitationExpired,
            summary: sprintf('Invitation expired for %s', $invitation->email),
            scope: AuditScope::Organization,
            organizationId: $invitation->organization_id,
            entityType: AuditEntityType::Invitation,
            entityPublicId: $invitation->public_id,
            entityLabel: $invitation->email,
            beforeState: self::diff(AuditEntityType::Invitation, 'status', InvitationStatus::Pending->value, InvitationStatus::Expired->value),
            afterState: self::snapshot(AuditEntityType::Invitation, [
                'status' => $invitation->status->value,
            ]),
            retentionClass: AuditRetentionClass::Standard,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordApplicationInstalled(
        OrganizationApplication $installation,
        Application $application,
        TenantContext $context,
    ): void {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: sprintf('%s installed', $application->name),
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::OrganizationApplication,
            entityPublicId: $installation->public_id,
            entityLabel: $application->name,
            afterState: self::snapshot(AuditEntityType::OrganizationApplication, [
                'status' => $installation->status->value,
                'installed_version' => $installation->installed_version,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordApplicationEnabled(
        OrganizationApplication $installation,
        TenantContext $context,
    ): void {
        $this->recordApplicationTransition(
            action: AuditAction::ApplicationEnabled,
            installation: $installation,
            context: $context,
            from: OrganizationApplicationStatus::Disabled,
            to: OrganizationApplicationStatus::Active,
            summary: sprintf('%s enabled', $installation->application->name),
        );
    }

    public function recordApplicationDisabled(
        OrganizationApplication $installation,
        TenantContext $context,
    ): void {
        $this->recordApplicationTransition(
            action: AuditAction::ApplicationDisabled,
            installation: $installation,
            context: $context,
            from: OrganizationApplicationStatus::Active,
            to: OrganizationApplicationStatus::Disabled,
            summary: sprintf('%s disabled', $installation->application->name),
        );
    }

    public function recordApplicationUninstalled(
        OrganizationApplication $installation,
        TenantContext $context,
    ): void {
        $installation->loadMissing('application');

        $this->safeRecord(new AuditEventData(
            action: AuditAction::ApplicationUninstalled,
            summary: sprintf('%s uninstalled', $installation->application->name),
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::OrganizationApplication,
            entityPublicId: $installation->public_id,
            entityLabel: $installation->application->name,
            beforeState: self::snapshot(AuditEntityType::OrganizationApplication, [
                'status' => $installation->status->value,
                'installed_version' => $installation->installed_version,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordCoreActionBlocked(
        OrganizationApplication $installation,
        TenantContext $context,
        string $attemptedAction,
    ): void {
        $installation->loadMissing('application');

        $this->safeRecord(new AuditEventData(
            action: AuditAction::SecurityCoreActionBlocked,
            summary: sprintf('Core application %s action blocked', $attemptedAction),
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::OrganizationApplication,
            entityPublicId: $installation->public_id,
            entityLabel: $installation->application->name,
            metadata: [
                'attempted_action' => $attemptedAction,
            ],
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Standard,
            severity: AuditSeverity::Warning,
        ));
    }

    public function recordWorkspaceRuntimeGenerated(TenantContext $context, WorkspaceRuntimeContext $runtime): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::WorkspaceRuntimeGenerated,
            summary: 'Workspace runtime generated',
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::Workspace,
            entityPublicId: $context->workspacePublicId,
            entityLabel: $context->workspace->name,
            metadata: [
                'runtime_version' => $runtime->runtimeVersion,
                'settings_version' => $runtime->settingsVersion,
                'active_application_count' => count($runtime->activeApplications),
            ],
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Info,
        ));
    }

    public function recordWorkspaceRuntimeFailed(TenantContext $context): void
    {
        $this->safeRecord(new AuditEventData(
            action: AuditAction::WorkspaceRuntimeFailed,
            summary: 'Workspace runtime generation failed',
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::Workspace,
            entityPublicId: $context->workspacePublicId,
            entityLabel: $context->workspace->name,
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Ephemeral,
            severity: AuditSeverity::Warning,
        ));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public static function snapshot(AuditEntityType $entityType, array $fields): array
    {
        return [
            'snapshot' => $fields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function diff(
        AuditEntityType $entityType,
        string $field,
        mixed $from,
        mixed $to,
    ): array {
        return [
            'fields' => [
                $field => [
                    'from' => $from,
                    'to' => $to,
                ],
            ],
        ];
    }

    private function recordApplicationTransition(
        AuditAction $action,
        OrganizationApplication $installation,
        TenantContext $context,
        OrganizationApplicationStatus $from,
        OrganizationApplicationStatus $to,
        string $summary,
    ): void {
        $installation->loadMissing('application');

        $this->safeRecord(new AuditEventData(
            action: $action,
            summary: $summary,
            scope: AuditScope::Organization,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            entityType: AuditEntityType::OrganizationApplication,
            entityPublicId: $installation->public_id,
            entityLabel: $installation->application->name,
            beforeState: self::diff(AuditEntityType::OrganizationApplication, 'status', $from->value, $to->value),
            afterState: self::snapshot(AuditEntityType::OrganizationApplication, [
                'status' => $to->value,
            ]),
            actorType: AuditActorType::User,
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
            retentionClass: AuditRetentionClass::Permanent,
            severity: AuditSeverity::Info,
        ));
    }
}
