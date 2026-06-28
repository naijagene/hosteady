<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardVisibility: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Private = 'private';
    case Public = 'public';
}
