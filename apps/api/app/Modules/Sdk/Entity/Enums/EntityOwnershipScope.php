<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityOwnershipScope: string
{
    case Platform = 'platform';
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Module = 'module';
}
