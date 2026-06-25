<?php

namespace App\Enums;

enum SettingDefinitionScope: string
{
    case Workspace = 'workspace';
    case Organization = 'organization';
}
