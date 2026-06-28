<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleScope: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Module = 'module';
    case Entity = 'entity';
    case Global = 'global';
}
