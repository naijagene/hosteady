<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Authenticated = 'authenticated';
    case PermissionBased = 'permission_based';
    case RoleBased = 'role_based';
    case WorkspaceBased = 'workspace_based';
    case OrganizationBased = 'organization_based';
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Role = 'role';
    case Hidden = 'hidden';
    case Custom = 'custom';
}
