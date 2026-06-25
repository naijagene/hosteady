<?php

namespace App\Enums;

enum WorkspaceSettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Array = 'array';
    case Json = 'json';
}
