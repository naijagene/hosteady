<?php

namespace App\Enums;

enum AuditAction: string
{
    case AuthLoginSucceeded = 'auth.login.succeeded';
    case AuthLoginFailed = 'auth.login.failed';
    case AuthLogoutSucceeded = 'auth.logout.succeeded';
    case AuthTokenRevoked = 'auth.token.revoked';

    case OrganizationCreated = 'organization.created';
    case OrganizationUpdated = 'organization.updated';
    case OrganizationArchived = 'organization.archived';
    case OrganizationStatusChanged = 'organization.status.changed';

    case MembershipCreated = 'membership.created';
    case MembershipUpdated = 'membership.updated';
    case MembershipRemoved = 'membership.removed';
    case MembershipRoleAssigned = 'membership.role.assigned';
    case MembershipRoleRemoved = 'membership.role.removed';

    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RolePermissionGranted = 'role.permission.granted';
    case RolePermissionRevoked = 'role.permission.revoked';

    case InvitationCreated = 'invitation.created';
    case InvitationRevoked = 'invitation.revoked';
    case InvitationAccepted = 'invitation.accepted';
    case InvitationExpired = 'invitation.expired';

    case WorkspaceCreated = 'workspace.created';
    case WorkspaceUpdated = 'workspace.updated';
    case WorkspaceArchived = 'workspace.archived';

    case ApplicationInstalled = 'application.installed';
    case ApplicationEnabled = 'application.enabled';
    case ApplicationDisabled = 'application.disabled';
    case ApplicationUninstalled = 'application.uninstalled';

    case WorkspaceApplicationArchived = 'workspace.application.archived';

    case SecurityPermissionDenied = 'security.permission_denied';
    case SecurityInvalidToken = 'security.invalid_token';
    case SecurityRoleEscalationAttempt = 'security.role_escalation_attempt';
    case SecurityAccessDenied = 'security.access.denied';
    case SecurityTenantRejected = 'security.tenant.rejected';
    case SecurityTenantInvalidHeader = 'security.tenant.invalid_header';
    case SecurityCoreActionBlocked = 'security.core_action.blocked';
    case SecurityAuthUnauthenticated = 'security.auth.unauthenticated';

    case TenantContextSelected = 'tenant.context.selected';

    case WorkspaceRuntimeGenerated = 'workspace.runtime.generated';
    case WorkspaceRuntimeFailed = 'workspace.runtime.failed';

    public function category(): AuditCategory
    {
        return match ($this) {
            self::AuthLoginSucceeded,
            self::AuthLoginFailed,
            self::AuthLogoutSucceeded,
            self::AuthTokenRevoked => AuditCategory::Authentication,

            self::OrganizationCreated,
            self::OrganizationUpdated,
            self::OrganizationArchived,
            self::OrganizationStatusChanged => AuditCategory::Organization,

            self::MembershipCreated,
            self::MembershipUpdated,
            self::MembershipRemoved,
            self::MembershipRoleAssigned,
            self::MembershipRoleRemoved => AuditCategory::Membership,

            self::RoleCreated,
            self::RoleUpdated,
            self::RolePermissionGranted,
            self::RolePermissionRevoked => AuditCategory::Role,

            self::InvitationCreated,
            self::InvitationRevoked,
            self::InvitationAccepted,
            self::InvitationExpired => AuditCategory::Invitation,

            self::WorkspaceCreated,
            self::WorkspaceUpdated,
            self::WorkspaceArchived => AuditCategory::Workspace,

            self::WorkspaceRuntimeGenerated,
            self::WorkspaceRuntimeFailed => AuditCategory::Workspace,

            self::ApplicationInstalled,
            self::ApplicationEnabled,
            self::ApplicationDisabled,
            self::ApplicationUninstalled,
            self::WorkspaceApplicationArchived => AuditCategory::Application,

            self::SecurityAccessDenied,
            self::SecurityPermissionDenied,
            self::SecurityInvalidToken,
            self::SecurityRoleEscalationAttempt,
            self::SecurityTenantRejected,
            self::SecurityTenantInvalidHeader,
            self::SecurityCoreActionBlocked,
            self::SecurityAuthUnauthenticated => AuditCategory::Security,

            self::TenantContextSelected => AuditCategory::Tenant,
        };
    }

    public function defaultSeverity(): AuditSeverity
    {
        return match ($this) {
            self::AuthLoginFailed,
            self::OrganizationArchived,
            self::OrganizationStatusChanged,
            self::MembershipRemoved,
            self::WorkspaceArchived,
            self::ApplicationDisabled,
            self::ApplicationUninstalled,
            self::WorkspaceApplicationArchived,
            self::SecurityAccessDenied,
            self::SecurityPermissionDenied,
            self::SecurityInvalidToken,
            self::SecurityTenantRejected,
            self::SecurityTenantInvalidHeader,
            self::SecurityCoreActionBlocked,
            self::SecurityAuthUnauthenticated => AuditSeverity::Warning,

            self::WorkspaceRuntimeFailed => AuditSeverity::Warning,

            self::SecurityRoleEscalationAttempt => AuditSeverity::Critical,

            default => AuditSeverity::Info,
        };
    }

    public function defaultRetention(): AuditRetentionClass
    {
        return match ($this) {
            self::OrganizationCreated,
            self::OrganizationUpdated,
            self::OrganizationArchived,
            self::OrganizationStatusChanged,
            self::MembershipCreated,
            self::MembershipUpdated,
            self::MembershipRemoved,
            self::MembershipRoleAssigned,
            self::MembershipRoleRemoved,
            self::RoleCreated,
            self::RoleUpdated,
            self::RolePermissionGranted,
            self::RolePermissionRevoked,
            self::InvitationCreated,
            self::InvitationRevoked,
            self::InvitationAccepted,
            self::WorkspaceCreated,
            self::WorkspaceUpdated,
            self::WorkspaceArchived,
            self::ApplicationInstalled,
            self::ApplicationEnabled,
            self::ApplicationDisabled,
            self::ApplicationUninstalled,
            self::WorkspaceApplicationArchived => AuditRetentionClass::Permanent,

            self::AuthLoginSucceeded,
            self::AuthLogoutSucceeded,
            self::AuthTokenRevoked,
            self::InvitationExpired,
            self::SecurityCoreActionBlocked,
            self::SecurityRoleEscalationAttempt => AuditRetentionClass::Standard,

            self::AuthLoginFailed,
            self::SecurityAccessDenied,
            self::SecurityPermissionDenied,
            self::SecurityInvalidToken,
            self::SecurityTenantRejected,
            self::SecurityTenantInvalidHeader,
            self::SecurityAuthUnauthenticated,
            self::TenantContextSelected,
            self::WorkspaceRuntimeGenerated,
            self::WorkspaceRuntimeFailed => AuditRetentionClass::Ephemeral,
        };
    }

    public function defaultScope(): AuditScope
    {
        return match ($this) {
            self::AuthLoginSucceeded,
            self::AuthLoginFailed,
            self::AuthLogoutSucceeded,
            self::AuthTokenRevoked,
            self::SecurityInvalidToken => AuditScope::Platform,

            default => AuditScope::Organization,
        };
    }
}
