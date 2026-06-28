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
    case NotificationCreated = 'notification.created';
    case NotificationUpdated = 'notification.updated';
    case NotificationDeleted = 'notification.deleted';
    case NotificationDelivered = 'notification.delivered';
    case NotificationDeliveryFailed = 'notification.delivery.failed';
    case NotificationTemplateCreated = 'notification.template.created';
    case NotificationTemplateUpdated = 'notification.template.updated';
    case NotificationBroadcastSent = 'notification.broadcast.sent';
    case RuleSetCreated = 'rule.set.created';
    case RuleSetUpdated = 'rule.set.updated';
    case RuleSetDeleted = 'rule.set.deleted';
    case RuleSetEnabled = 'rule.set.enabled';
    case RuleSetDisabled = 'rule.set.disabled';
    case RuleDefinitionCreated = 'rule.definition.created';
    case RuleDefinitionUpdated = 'rule.definition.updated';
    case RuleDefinitionDeleted = 'rule.definition.deleted';
    case RuleDefinitionEnabled = 'rule.definition.enabled';
    case RuleDefinitionDisabled = 'rule.definition.disabled';
    case RuleEvaluated = 'rule.evaluated';
    case RuleExecuted = 'rule.executed';
    case RuleViolationRecorded = 'rule.violation.recorded';
    case RuleActivityLogged = 'rule.activity.logged';
    case IntegrationEventPublished = 'integration.event.published';
    case IntegrationEventReplayed = 'integration.event.replayed';
    case IntegrationConnectorCreated = 'integration.connector.created';
    case IntegrationEndpointCreated = 'integration.endpoint.created';
    case IntegrationSubscriptionCreated = 'integration.subscription.created';
    case IntegrationDispatchCompleted = 'integration.dispatch.completed';
    case IntegrationDispatchFailed = 'integration.dispatch.failed';
    case IntegrationDeadLetterEnqueued = 'integration.dead_letter.enqueued';
    case IntegrationDeadLetterResolved = 'integration.dead_letter.resolved';
    case ApplicationRuntimeRegistered = 'application.registered';
    case ApplicationRuntimeEnabled = 'application.runtime.enabled';
    case ApplicationRuntimeDisabled = 'application.runtime.disabled';
    case ApplicationNavigationUpdated = 'application.navigation.updated';
    case ApplicationWorkspaceCreated = 'application.workspace.created';
    case UiPageRegistered = 'ui.page.registered';
    case UiPageUpdated = 'ui.page.updated';
    case UiPageRendered = 'ui.page.rendered';
    case UiLayoutRegistered = 'ui.layout.registered';
    case UiLayoutUpdated = 'ui.layout.updated';
    case UiComponentRegistered = 'ui.component.registered';
    case UiComponentUpdated = 'ui.component.updated';
    case UiPersonalizationUpdated = 'ui.personalization.updated';
    case UiActivityLogged = 'ui.activity.logged';

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

    case WorkflowCreated = 'workflow.created';
    case WorkflowUpdated = 'workflow.updated';
    case WorkflowPublished = 'workflow.published';
    case WorkflowArchived = 'workflow.archived';
    case WorkflowValidated = 'workflow.validated';
    case WorkflowCategoryCreated = 'workflow.category.created';
    case WorkflowCategoryUpdated = 'workflow.category.updated';

    case WorkflowExecutionStarted = 'workflow.execution.started';
    case WorkflowExecutionCompleted = 'workflow.execution.completed';
    case WorkflowExecutionFailed = 'workflow.execution.failed';
    case WorkflowExecutionCancelled = 'workflow.execution.cancelled';
    case WorkflowExecutionResumed = 'workflow.execution.resumed';
    case WorkflowExecutionNodeExecuted = 'workflow.execution.node.executed';
    case WorkflowExecutionCondition = 'workflow.execution.condition';
    case WorkflowExecutionVariableResolved = 'workflow.execution.variable.resolved';

    case TaskCreated = 'task.created';
    case TaskAssigned = 'task.assigned';
    case TaskOpened = 'task.opened';
    case TaskCompleted = 'task.completed';
    case TaskCancelled = 'task.cancelled';
    case TaskCommented = 'task.commented';
    case TaskEscalated = 'task.escalated';

    case ApprovalRequested = 'approval.requested';
    case ApprovalApproved = 'approval.approved';
    case ApprovalRejected = 'approval.rejected';
    case ApprovalCompleted = 'approval.completed';

    case WorkflowAutomationRuleCreated = 'workflow.automation.rule.created';
    case WorkflowAutomationRuleEnabled = 'workflow.automation.rule.enabled';
    case WorkflowAutomationRuleDisabled = 'workflow.automation.rule.disabled';
    case WorkflowAutomationRuleDeleted = 'workflow.automation.rule.deleted';
    case WorkflowTriggerExecuted = 'workflow.trigger.executed';
    case WorkflowTriggerFailed = 'workflow.trigger.failed';
    case WorkflowTimerCreated = 'workflow.timer.created';
    case WorkflowTimerExecuted = 'workflow.timer.executed';
    case WorkflowTimerFailed = 'workflow.timer.failed';
    case WorkflowTimerCancelled = 'workflow.timer.cancelled';

    case WorkflowDesignerCanvasSaved = 'workflow.designer.canvas.saved';
    case WorkflowDesignerSnapshotCreated = 'workflow.designer.snapshot.created';
    case WorkflowDesignerSnapshotDiffed = 'workflow.designer.snapshot.diffed';
    case WorkflowCloned = 'workflow.designer.workflow.cloned';
    case WorkflowImported = 'workflow.designer.workflow.imported';
    case WorkflowExported = 'workflow.designer.workflow.exported';
    case WorkflowDesignerTemplateCreated = 'workflow.designer.template.created';
    case WorkflowDesignerPreviewGenerated = 'workflow.designer.preview.generated';

    case WorkflowMarketplacePackageCreated = 'workflow.marketplace.package.created';
    case WorkflowMarketplacePackageUpdated = 'workflow.marketplace.package.updated';
    case WorkflowMarketplacePackageDeleted = 'workflow.marketplace.package.deleted';
    case WorkflowMarketplacePackageVersionPublished = 'workflow.marketplace.package.version.published';
    case WorkflowMarketplaceInstalled = 'workflow.marketplace.installed';
    case WorkflowMarketplaceUpgraded = 'workflow.marketplace.upgraded';
    case WorkflowMarketplaceRollback = 'workflow.marketplace.rollback';
    case WorkflowMarketplaceUninstalled = 'workflow.marketplace.uninstalled';
    case WorkflowMarketplaceExported = 'workflow.marketplace.exported';
    case WorkflowMarketplaceImported = 'workflow.marketplace.imported';
    case WorkflowMarketplaceCompatibilityChecked = 'workflow.marketplace.compatibility.checked';

    case BusinessModuleRegistered = 'business.module.registered';
    case BusinessModuleInstalled = 'business.module.installed';
    case BusinessModuleEnabled = 'business.module.enabled';
    case BusinessModuleDisabled = 'business.module.disabled';
    case BusinessModuleUninstalled = 'business.module.uninstalled';
    case BusinessModuleScaffolded = 'business.module.scaffolded';
    case BusinessModuleValidated = 'business.module.validated';

    case EntityDefinitionRegistered = 'entity.definition.registered';
    case EntityDefinitionUpdated = 'entity.definition.updated';
    case EntityRelationshipRegistered = 'entity.relationship.registered';
    case EntityActivityLogged = 'entity.activity.logged';
    case EntityCommentCreated = 'entity.comment.created';
    case EntityCommentDeleted = 'entity.comment.deleted';
    case EntityTagCreated = 'entity.tag.created';
    case EntityTagAttached = 'entity.tag.attached';
    case EntityTagDetached = 'entity.tag.detached';
    case EntityLifecycleEvent = 'entity.lifecycle.event';

    case DataRecordCreated = 'data.record.created';
    case DataRecordUpdated = 'data.record.updated';
    case DataRecordDeleted = 'data.record.deleted';
    case DataRecordRestored = 'data.record.restored';
    case DataRecordVersioned = 'data.record.versioned';
    case DataRecordLinked = 'data.record.linked';
    case DataRecordUnlinked = 'data.record.unlinked';
    case DataRecordActivityLogged = 'data.record.activity.logged';
    case DataRecordQueried = 'data.record.queried';

    case DocumentUploaded = 'document.uploaded';
    case DocumentUpdated = 'document.updated';
    case DocumentDeleted = 'document.deleted';
    case DocumentVersionCreated = 'document.version.created';
    case DocumentVersionRestored = 'document.version.restored';
    case DocumentVersionDeleted = 'document.version.deleted';
    case DocumentAttached = 'document.attached';
    case DocumentDetached = 'document.detached';
    case DocumentPreviewRequested = 'document.preview.requested';
    case DocumentThumbnailRequested = 'document.thumbnail.requested';
    case DocumentScanRequested = 'document.scan.requested';
    case DocumentOcrRequested = 'document.ocr.requested';
    case DocumentActivityLogged = 'document.activity.logged';

    case FormDefinitionRegistered = 'form.definition.registered';
    case FormDefinitionUpdated = 'form.definition.updated';
    case FormRendered = 'form.rendered';
    case FormValidated = 'form.validated';
    case FormSubmitted = 'form.submitted';
    case FormDraftSaved = 'form.draft.saved';
    case FormDraftDeleted = 'form.draft.deleted';
    case FormActivityLogged = 'form.activity.logged';

    case TableDefinitionRegistered = 'table.definition.registered';
    case TableDefinitionUpdated = 'table.definition.updated';
    case TableRendered = 'table.rendered';
    case TableQueried = 'table.queried';
    case TableViewSaved = 'table.view.saved';
    case TableViewDeleted = 'table.view.deleted';
    case TableActivityLogged = 'table.activity.logged';

    case DashboardDefinitionRegistered = 'dashboard.definition.registered';
    case DashboardDefinitionUpdated = 'dashboard.definition.updated';
    case DashboardRendered = 'dashboard.rendered';
    case DashboardWidgetCreated = 'dashboard.widget.created';
    case DashboardWidgetUpdated = 'dashboard.widget.updated';
    case DashboardWidgetDeleted = 'dashboard.widget.deleted';
    case DashboardViewCreated = 'dashboard.view.created';
    case DashboardViewUpdated = 'dashboard.view.updated';
    case DashboardViewDeleted = 'dashboard.view.deleted';
    case DashboardActivityLogged = 'dashboard.activity.logged';

    case ReportDefinitionRegistered = 'report.definition.registered';
    case ReportDefinitionUpdated = 'report.definition.updated';
    case ReportRendered = 'report.rendered';
    case ReportRunStarted = 'report.run.started';
    case ReportRunCompleted = 'report.run.completed';
    case ReportRunFailed = 'report.run.failed';
    case ReportExportRequested = 'report.export.requested';
    case ReportExportCompleted = 'report.export.completed';
    case ReportExportFailed = 'report.export.failed';
    case ReportScheduleCreated = 'report.schedule.created';
    case ReportScheduleUpdated = 'report.schedule.updated';
    case ReportScheduleDeleted = 'report.schedule.deleted';
    case ReportActivityLogged = 'report.activity.logged';

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
            self::NotificationCreated,
            self::NotificationUpdated,
            self::NotificationDeleted,
            self::NotificationDelivered,
            self::NotificationDeliveryFailed,
            self::NotificationTemplateCreated,
            self::NotificationTemplateUpdated,
            self::NotificationBroadcastSent,
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
            self::IndexUpdated,
            self::WorkflowCreated,
            self::WorkflowUpdated,
            self::WorkflowPublished,
            self::WorkflowArchived,
            self::WorkflowValidated,
            self::WorkflowCategoryCreated,
            self::WorkflowCategoryUpdated,
            self::WorkflowExecutionStarted,
            self::WorkflowExecutionCompleted,
            self::WorkflowExecutionFailed,
            self::WorkflowExecutionCancelled,
            self::WorkflowExecutionResumed,
            self::WorkflowExecutionNodeExecuted,
            self::WorkflowExecutionCondition,
            self::WorkflowExecutionVariableResolved,
            self::TaskCreated,
            self::TaskAssigned,
            self::TaskOpened,
            self::TaskCompleted,
            self::TaskCancelled,
            self::TaskCommented,
            self::TaskEscalated,
            self::ApprovalRequested,
            self::ApprovalApproved,
            self::ApprovalRejected,
            self::ApprovalCompleted,
            self::WorkflowAutomationRuleCreated,
            self::WorkflowAutomationRuleEnabled,
            self::WorkflowAutomationRuleDisabled,
            self::WorkflowAutomationRuleDeleted,
            self::WorkflowTriggerExecuted,
            self::WorkflowTriggerFailed,
            self::WorkflowTimerCreated,
            self::WorkflowTimerExecuted,
            self::WorkflowTimerFailed,
            self::WorkflowTimerCancelled,
            self::WorkflowDesignerCanvasSaved,
            self::WorkflowDesignerSnapshotCreated,
            self::WorkflowDesignerSnapshotDiffed,
            self::WorkflowCloned,
            self::WorkflowImported,
            self::WorkflowExported,
            self::WorkflowDesignerTemplateCreated,
            self::WorkflowDesignerPreviewGenerated,
            self::WorkflowMarketplacePackageCreated,
            self::WorkflowMarketplacePackageUpdated,
            self::WorkflowMarketplacePackageDeleted,
            self::WorkflowMarketplacePackageVersionPublished,
            self::WorkflowMarketplaceInstalled,
            self::WorkflowMarketplaceUpgraded,
            self::WorkflowMarketplaceRollback,
            self::WorkflowMarketplaceUninstalled,
            self::WorkflowMarketplaceExported,
            self::WorkflowMarketplaceImported,
            self::WorkflowMarketplaceCompatibilityChecked,
            self::BusinessModuleRegistered,
            self::BusinessModuleInstalled,
            self::BusinessModuleEnabled,
            self::BusinessModuleDisabled,
            self::BusinessModuleUninstalled,
            self::BusinessModuleScaffolded,
            self::BusinessModuleValidated,
            self::EntityDefinitionRegistered,
            self::EntityDefinitionUpdated,
            self::EntityRelationshipRegistered,
            self::EntityActivityLogged,
            self::EntityCommentCreated,
            self::EntityCommentDeleted,
            self::EntityTagCreated,
            self::EntityTagAttached,
            self::EntityTagDetached,
            self::EntityLifecycleEvent,
            self::DataRecordCreated,
            self::DataRecordUpdated,
            self::DataRecordDeleted,
            self::DataRecordRestored,
            self::DataRecordVersioned,
            self::DataRecordLinked,
            self::DataRecordUnlinked,
            self::DataRecordActivityLogged,
            self::DataRecordQueried,
            self::DocumentUploaded,
            self::DocumentUpdated,
            self::DocumentDeleted,
            self::DocumentVersionCreated,
            self::DocumentVersionRestored,
            self::DocumentVersionDeleted,
            self::DocumentAttached,
            self::DocumentDetached,
            self::DocumentPreviewRequested,
            self::DocumentThumbnailRequested,
            self::DocumentScanRequested,
            self::DocumentOcrRequested,
            self::DocumentActivityLogged,
            self::RuleSetCreated,
            self::RuleSetUpdated,
            self::RuleSetDeleted,
            self::RuleSetEnabled,
            self::RuleSetDisabled,
            self::RuleDefinitionCreated,
            self::RuleDefinitionUpdated,
            self::RuleDefinitionDeleted,
            self::RuleDefinitionEnabled,
            self::RuleDefinitionDisabled,
            self::RuleEvaluated,
            self::RuleExecuted,
            self::RuleViolationRecorded,
            self::RuleActivityLogged,
            self::IntegrationEventPublished,
            self::IntegrationEventReplayed,
            self::IntegrationConnectorCreated,
            self::IntegrationEndpointCreated,
            self::IntegrationSubscriptionCreated,
            self::IntegrationDispatchCompleted,
            self::IntegrationDispatchFailed,
            self::IntegrationDeadLetterEnqueued,
            self::IntegrationDeadLetterResolved,
            self::ApplicationRuntimeRegistered,
            self::ApplicationRuntimeEnabled,
            self::ApplicationRuntimeDisabled,
            self::ApplicationNavigationUpdated,
            self::ApplicationWorkspaceCreated,
            self::UiPageRegistered,
            self::UiPageUpdated,
            self::UiPageRendered,
            self::UiLayoutRegistered,
            self::UiLayoutUpdated,
            self::UiComponentRegistered,
            self::UiComponentUpdated,
            self::UiPersonalizationUpdated,
            self::UiActivityLogged,
            self::FormDefinitionRegistered,
            self::FormDefinitionUpdated,
            self::FormRendered,
            self::FormValidated,
            self::FormSubmitted,
            self::FormDraftSaved,
            self::FormDraftDeleted,
            self::FormActivityLogged => AuditCategory::Application,

            self::TableDefinitionRegistered,
            self::TableDefinitionUpdated,
            self::TableRendered,
            self::TableQueried,
            self::TableViewSaved,
            self::TableViewDeleted,
            self::TableActivityLogged => AuditCategory::Application,

            self::DashboardDefinitionRegistered,
            self::DashboardDefinitionUpdated,
            self::DashboardRendered,
            self::DashboardWidgetCreated,
            self::DashboardWidgetUpdated,
            self::DashboardWidgetDeleted,
            self::DashboardViewCreated,
            self::DashboardViewUpdated,
            self::DashboardViewDeleted,
            self::DashboardActivityLogged => AuditCategory::Application,

            self::ReportDefinitionRegistered,
            self::ReportDefinitionUpdated,
            self::ReportRendered,
            self::ReportRunStarted,
            self::ReportRunCompleted,
            self::ReportRunFailed,
            self::ReportExportRequested,
            self::ReportExportCompleted,
            self::ReportExportFailed,
            self::ReportScheduleCreated,
            self::ReportScheduleUpdated,
            self::ReportScheduleDeleted,
            self::ReportActivityLogged => AuditCategory::Application,

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
            self::SchedulerTaskFailed,
            self::WorkflowExecutionFailed,
            self::ApprovalRejected,
            self::WorkflowTriggerFailed,
            self::WorkflowTimerFailed => AuditSeverity::Warning,

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
            self::NotificationCreated,
            self::NotificationUpdated,
            self::NotificationDeleted,
            self::NotificationDelivered,
            self::NotificationDeliveryFailed,
            self::NotificationTemplateCreated,
            self::NotificationTemplateUpdated,
            self::NotificationBroadcastSent,
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
            self::IndexUpdated,
            self::WorkflowCreated,
            self::WorkflowUpdated,
            self::WorkflowPublished,
            self::WorkflowArchived,
            self::WorkflowValidated,
            self::WorkflowCategoryCreated,
            self::WorkflowCategoryUpdated,
            self::WorkflowExecutionStarted,
            self::WorkflowExecutionCompleted,
            self::WorkflowExecutionFailed,
            self::WorkflowExecutionCancelled,
            self::WorkflowExecutionResumed,
            self::WorkflowExecutionNodeExecuted,
            self::WorkflowExecutionCondition,
            self::WorkflowExecutionVariableResolved,
            self::TaskCreated,
            self::TaskAssigned,
            self::TaskOpened,
            self::TaskCompleted,
            self::TaskCancelled,
            self::TaskCommented,
            self::TaskEscalated,
            self::ApprovalRequested,
            self::ApprovalApproved,
            self::ApprovalRejected,
            self::ApprovalCompleted,
            self::WorkflowAutomationRuleCreated,
            self::WorkflowAutomationRuleEnabled,
            self::WorkflowAutomationRuleDisabled,
            self::WorkflowAutomationRuleDeleted,
            self::WorkflowTriggerExecuted,
            self::WorkflowTriggerFailed,
            self::WorkflowTimerCreated,
            self::WorkflowTimerExecuted,
            self::WorkflowTimerFailed,
            self::WorkflowTimerCancelled,
            self::RuleSetCreated,
            self::RuleSetUpdated,
            self::RuleSetDeleted,
            self::RuleSetEnabled,
            self::RuleSetDisabled,
            self::RuleDefinitionCreated,
            self::RuleDefinitionUpdated,
            self::RuleDefinitionDeleted,
            self::RuleDefinitionEnabled,
            self::RuleDefinitionDisabled,
            self::RuleEvaluated,
            self::RuleExecuted,
            self::RuleViolationRecorded,
            self::RuleActivityLogged,
            self::IntegrationEventPublished,
            self::IntegrationEventReplayed,
            self::IntegrationConnectorCreated,
            self::IntegrationEndpointCreated,
            self::IntegrationSubscriptionCreated,
            self::IntegrationDispatchCompleted,
            self::IntegrationDispatchFailed,
            self::IntegrationDeadLetterEnqueued,
            self::IntegrationDeadLetterResolved,
            self::ApplicationRuntimeRegistered,
            self::ApplicationRuntimeEnabled,
            self::ApplicationRuntimeDisabled,
            self::ApplicationNavigationUpdated,
            self::ApplicationWorkspaceCreated,
            self::UiPageRegistered,
            self::UiPageUpdated,
            self::UiPageRendered,
            self::UiLayoutRegistered,
            self::UiLayoutUpdated,
            self::UiComponentRegistered,
            self::UiComponentUpdated,
            self::UiPersonalizationUpdated,
            self::UiActivityLogged => AuditRetentionClass::Ephemeral,
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
