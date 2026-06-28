<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiRegionType: string
{
    case Header = 'header';
    case Sidebar = 'sidebar';
    case Content = 'content';
    case Footer = 'footer';
    case Toolbar = 'toolbar';
    case Card = 'card';
    case Tab = 'tab';
    case Modal = 'modal';
    case Drawer = 'drawer';
    case WidgetArea = 'widget_area';
    case Custom = 'custom';
}
