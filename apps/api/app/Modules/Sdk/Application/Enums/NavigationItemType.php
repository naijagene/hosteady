<?php

namespace App\Modules\Sdk\Application\Enums;

enum NavigationItemType: string
{
    case Group = 'group';
    case Item = 'item';
    case Divider = 'divider';
    case Link = 'link';
    case Module = 'module';
}
