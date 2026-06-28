<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiComponentType: string
{
    case Form = 'form';
    case Table = 'table';
    case Dashboard = 'dashboard';
    case Report = 'report';
    case Chart = 'chart';
    case Metric = 'metric';
    case DocumentList = 'document_list';
    case NotificationList = 'notification_list';
    case WorkflowQueue = 'workflow_queue';
    case ApprovalQueue = 'approval_queue';
    case ActivityFeed = 'activity_feed';
    case NavigationMenu = 'navigation_menu';
    case Custom = 'custom';
}
