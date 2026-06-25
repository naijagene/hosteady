<?php

namespace App\Enums;

enum WorkspaceSettingChangeType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Reset = 'reset';
    case Deleted = 'deleted';
}
