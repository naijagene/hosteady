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
    case User = 'user';
}
