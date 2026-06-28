<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiLayoutType: string
{
    case SingleColumn = 'single_column';
    case TwoColumn = 'two_column';
    case ThreeColumn = 'three_column';
    case Sidebar = 'sidebar';
    case HeaderContent = 'header_content';
    case DashboardGrid = 'dashboard_grid';
    case Tabbed = 'tabbed';
    case Wizard = 'wizard';
    case SplitPane = 'split_pane';
    case Custom = 'custom';
}
