<?php

namespace App\Modules\Sdk\Application\Enums;

enum ApplicationVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Organization = 'organization';
    case Workspace = 'workspace';
}
