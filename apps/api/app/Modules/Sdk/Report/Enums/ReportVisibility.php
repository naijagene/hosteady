<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportVisibility: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Private = 'private';
    case Public = 'public';
}
