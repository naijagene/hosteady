<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentVisibility: string
{
    case Private = 'private';
    case Workspace = 'workspace';
    case Organization = 'organization';
    case Public = 'public';
}
