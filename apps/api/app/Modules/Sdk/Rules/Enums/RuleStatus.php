<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleStatus: string
{
    case Draft = 'draft';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Archived = 'archived';
}
