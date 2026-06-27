<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableVisibility: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Private = 'private';
}
