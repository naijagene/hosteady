<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Role = 'role';
}
