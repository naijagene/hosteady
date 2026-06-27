<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityVisibility: string
{
    case Private = 'private';
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Public = 'public';
}
