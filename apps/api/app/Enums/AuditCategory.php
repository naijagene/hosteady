<?php

namespace App\Enums;

enum AuditCategory: string
{
    case Authentication = 'authentication';
    case Organization = 'organization';
    case Membership = 'membership';
    case Role = 'role';
    case Invitation = 'invitation';
    case Workspace = 'workspace';
    case Application = 'application';
    case Security = 'security';
    case Tenant = 'tenant';
}
