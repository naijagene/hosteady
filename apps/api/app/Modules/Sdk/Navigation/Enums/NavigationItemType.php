<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationItemType: string
{
    case Link = 'link';
    case Route = 'route';
    case Page = 'page';
    case Module = 'module';
    case Application = 'application';
    case Group = 'group';
    case Divider = 'divider';
    case Heading = 'heading';
    case External = 'external';
    case Action = 'action';
    case Custom = 'custom';
}
