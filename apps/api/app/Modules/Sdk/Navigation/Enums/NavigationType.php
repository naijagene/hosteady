<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationType: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Sidebar = 'sidebar';
    case Topbar = 'topbar';
    case Footer = 'footer';
    case Mobile = 'mobile';
    case Breadcrumb = 'breadcrumb';
    case Contextual = 'contextual';
    case CommandPalette = 'command_palette';
    case Custom = 'custom';
}
