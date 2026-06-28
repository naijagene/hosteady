<?php

namespace App\Enums;

enum AuditEntityType: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case OrganizationMembership = 'organization_membership';
    case Role = 'role';
    case Invitation = 'invitation';
    case Application = 'application';
    case OrganizationApplication = 'organization_application';
    case WorkspaceApplication = 'workspace_application';
    case PlatformFile = 'platform_file';
    case PlatformJob = 'platform_job';
    case ScheduledTask = 'scheduled_task';
    case PlatformSearchIndex = 'platform_search_index';
    case PlatformSavedSearch = 'platform_saved_search';
    case WorkflowDefinition = 'workflow_definition';
    case WorkflowCategory = 'workflow_category';
    case WorkflowInstance = 'workflow_instance';
    case WorkflowHumanTask = 'workflow_human_task';
    case WorkflowAutomationRule = 'workflow_automation_rule';
    case WorkflowTriggerExecution = 'workflow_trigger_execution';
    case WorkflowTimer = 'workflow_timer';
    case WorkflowCanvasSnapshot = 'workflow_canvas_snapshot';
    case WorkflowNodeTemplate = 'workflow_node_template';
    case WorkflowPackage = 'workflow_package';
    case WorkflowPackageInstall = 'workflow_package_install';
    case BusinessModule = 'business_module';
    case BusinessModuleInstallation = 'business_module_installation';
    case EntityDefinition = 'entity_definition';
    case EntityRelationship = 'entity_relationship';
    case EntityComment = 'entity_comment';
    case EntityTag = 'entity_tag';
    case EnterpriseEntityRecord = 'enterprise_entity_record';
    case FormDefinition = 'form_definition';
    case FormSubmission = 'form_submission';
    case FormDraft = 'form_draft';
    case TableDefinition = 'table_definition';
    case TableView = 'table_view';
    case DashboardDefinition = 'dashboard_definition';
    case DashboardWidget = 'dashboard_widget';
    case DashboardView = 'dashboard_view';
    case ReportDefinition = 'report_definition';
    case ReportRun = 'report_run';
    case ReportExport = 'report_export';
    case ReportSchedule = 'report_schedule';
    case EnterpriseDocument = 'enterprise_document';
    case EnterpriseNotification = 'enterprise_notification';
    case EnterpriseRule = 'enterprise_rule';
    case User = 'user';
}
