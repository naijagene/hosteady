<?php

namespace App\Enums;

enum SettingDefinitionStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
}
