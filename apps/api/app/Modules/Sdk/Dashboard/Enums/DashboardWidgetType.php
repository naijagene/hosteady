<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardWidgetType: string
{
    case KpiCard = 'kpi_card';
    case Statistic = 'statistic';
    case LineChart = 'line_chart';
    case BarChart = 'bar_chart';
    case PieChart = 'pie_chart';
    case DonutChart = 'donut_chart';
    case AreaChart = 'area_chart';
    case Table = 'table';
    case Form = 'form';
    case Calendar = 'calendar';
    case ActivityFeed = 'activity_feed';
    case WorkflowQueue = 'workflow_queue';
    case ApprovalQueue = 'approval_queue';
    case NotificationPanel = 'notification_panel';
    case Custom = 'custom';
}
