<?php

namespace App\Modules\Sdk\DataRepository\Enums;

enum EntityRecordVisibility: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Private = 'private';
}