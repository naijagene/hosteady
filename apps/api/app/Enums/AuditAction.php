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

    case ModuleInstallCompleted = 'module.install.completed';
    case ModuleUninstallCompleted = 'module.uninstall.completed';
    case ModuleWorkspaceEnabled = 'module.workspace.enabled';
    case ModuleWorkspaceDisabled = 'module.workspace.disabled';
    case ModuleSettingsUpdated = 'module.settings.updated';
    case ModuleRuntimeBefore = 'module.runtime.before';
    case ModuleRuntimeAfter = 'module.runtime.after';
    case ModuleRuntimeContribution = 'module.runtime.contribution';
    case ModuleValidationExecuted = 'module.validation.executed';
    case ModuleDoctorExecuted = 'module.doctor.executed';
    case ModuleDocumentationGenerated = 'module.documentation.generated';

    case PlatformEventDispatched = 'platform.event.dispatched';
    case PlatformEventProcessed = 'platform.event.processed';
    case PlatformEventFailed = 'platform.event.failed';
    case NotificationSent = 'notification.sent';
    case NotificationRead = 'notification.read';
    case NotificationPreferenceUpdated = 'notification.preference.updated';
    case ReferenceCatalogRegistered = 'reference.catalog.registered';

    case FileUploaded = 'file.uploaded';
    case FileUpdated = 'file.updated';
    case FileDeleted = 'file.deleted';
    case FileDownloaded = 'file.downloaded';
    case FileAccessDenied = 'file.access.denied';

    case PlatformJobDispatched = 'platform.job.dispatched';
    case PlatformJobStarted = 'platform.job.started';
    case PlatformJobCompleted = 'platform.job.completed';
    case PlatformJobFailed = 'platform.job.failed';
    case PlatformJobCancelled = 'platform.job.cancelled';

    case SchedulerTaskCreated = 'scheduler.task.created';
    case SchedulerTaskUpdated = 'scheduler.task.updated';
    case SchedulerTaskPaused = 'scheduler.task.paused';
    case SchedulerTaskResumed = 'scheduler.task.resumed';
    case SchedulerTaskCancelled = 'scheduler.task.cancelled';
    case SchedulerTaskExecuted = 'scheduler.task.executed';
    case SchedulerTaskFailed = 'scheduler.task.failed';

    case SearchExecuted = 'search.executed';
    case SearchSaved = 'search.saved';
    case SearchDeleted = 'search.deleted';
    case IndexUpdated = 'index.updated';

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

            self::ModuleInstallCompleted,
            self::ModuleUninstallCompleted,
            self::ModuleWorkspaceEnabled,
            self::ModuleWorkspaceDisabled,
            self::ModuleSettingsUpdated,
            self::ModuleRuntimeBefore,
            self::ModuleRuntimeAfter,
            self::ModuleRuntimeContribution,
            self::ModuleValidationExecuted,
            self::ModuleDoctorExecuted,
            self::ModuleDocumentationGenerated,
            self::PlatformEventDispatched,
            self::PlatformEventProcessed,
            self::PlatformEventFailed,
            self::NotificationSent,
            self::NotificationRead,
            self::NotificationPreferenceUpdated,
            self::ReferenceCatalogRegistered,
            self::FileUploaded,
            self::FileUpdated,
            self::FileDeleted,
            self::FileDownloaded,
            self::FileAccessDenied,
            self::PlatformJobDispatched,
            self::PlatformJobStarted,
            self::PlatformJobCompleted,
            self::PlatformJobFailed,
            self::PlatformJobCancelled,
            self::SchedulerTaskCreated,
            self::SchedulerTaskUpdated,
            self::SchedulerTaskPaused,
            self::SchedulerTaskResumed,
            self::SchedulerTaskCancelled,
            self::SchedulerTaskExecuted,
            self::SchedulerTaskFailed,
            self::SearchExecuted,
            self::SearchSaved,
            self::SearchDeleted,
            self::IndexUpdated => AuditCategory::Application,

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

            self::FileAccessDenied => AuditSeverity::Warning,

            self::PlatformJobFailed,
            self::SchedulerTaskFailed => AuditSeverity::Warning,

            self::WorkspaceRuntimeFailed => AuditSeverity::Warning,

            self::ModuleUninstallCompleted,
            self::ModuleWorkspaceDisabled => AuditSeverity::Warning,

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
            self::WorkspaceRuntimeFailed,
            self::ModuleInstallCompleted,
            self::ModuleUninstallCompleted,
            self::ModuleWorkspaceEnabled,
            self::ModuleWorkspaceDisabled,
            self::ModuleSettingsUpdated,
            self::ModuleRuntimeBefore,
            self::ModuleRuntimeAfter,
            self::ModuleRuntimeContribution,
            self::ModuleValidationExecuted,
            self::ModuleDoctorExecuted,
            self::ModuleDocumentationGenerated,
            self::PlatformEventDispatched,
            self::PlatformEventProcessed,
            self::PlatformEventFailed,
            self::NotificationSent,
            self::NotificationRead,
            self::NotificationPreferenceUpdated,
            self::ReferenceCatalogRegistered,
            self::FileUploaded,
            self::FileUpdated,
            self::FileDeleted,
            self::FileDownloaded,
            self::FileAccessDenied,
            self::PlatformJobDispatched,
            self::PlatformJobStarted,
            self::PlatformJobCompleted,
            self::PlatformJobFailed,
            self::PlatformJobCancelled,
            self::SchedulerTaskCreated,
            self::SchedulerTaskUpdated,
            self::SchedulerTaskPaused,
            self::SchedulerTaskResumed,
            self::SchedulerTaskCancelled,
            self::SchedulerTaskExecuted,
            self::SchedulerTaskFailed,
            self::SearchExecuted,
            self::SearchSaved,
            self::SearchDeleted,
            self::IndexUpdated => AuditRetentionClass::Ephemeral,
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
