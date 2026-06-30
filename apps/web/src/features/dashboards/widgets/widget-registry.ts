import { createElement, type ComponentType } from 'react'
import { ActivityWidget } from './ActivityWidget'
import { ChartWidget } from './ChartWidget'
import { FavoritesWidget } from './FavoritesWidget'
import { MetricWidget } from './MetricWidget'
import { NotificationWidget } from './NotificationWidget'
import { PlaceholderWidget } from './PlaceholderWidget'
import { QuickActionsWidget } from './QuickActionsWidget'
import { RecentItemsWidget } from './RecentItemsWidget'
import { ReportWidget } from './ReportWidget'
import { DocumentListWidget } from './DocumentListWidget'
import { TableWidget } from './TableWidget'
import { ApprovalQueueWidget, WorkflowInboxWidget, WorkflowStatusWidget } from '@/features/workflows'
import {
  AnnouncementWidget,
  MentionWidget,
  NotificationCenterWidget,
  ReminderWidget,
} from '@/features/notifications/widgets/notification-widgets'
import {
  ActivityFeedDashboardWidget,
  AuditSummaryDashboardWidget,
  SystemHistoryDashboardWidget,
} from '@/features/activity/widgets/activity-dashboard-widgets'
import type { DashboardWidgetComponentProps } from './types'

type WidgetComponent = ComponentType<DashboardWidgetComponentProps>

const registry = new Map<string, WidgetComponent>()

function register(type: string, component: WidgetComponent): void {
  registry.set(type.toLowerCase(), component)
}

register('metric', MetricWidget)
register('chart', ChartWidget)
register('table', TableWidget)
register('report', ReportWidget)
register('document_list', DocumentListWidget)
register('documents', DocumentListWidget)
register('workflow_queue', WorkflowInboxWidget)
register('approval_queue', ApprovalQueueWidget)
register('workflow_status', WorkflowStatusWidget)
register('notification', NotificationCenterWidget)
register('notifications', NotificationCenterWidget)
register('announcement', AnnouncementWidget)
register('announcements', AnnouncementWidget)
register('reminder', ReminderWidget)
register('reminders', ReminderWidget)
register('mention', MentionWidget)
register('mentions', MentionWidget)
register('notification_legacy', NotificationWidget)
register('activity', ActivityWidget)
register('activity_feed', ActivityFeedDashboardWidget)
register('audit_summary', AuditSummaryDashboardWidget)
register('system_history', SystemHistoryDashboardWidget)
register('quick_actions', QuickActionsWidget)
register('recent_items', RecentItemsWidget)
register('favorites', FavoritesWidget)
register('custom', PlaceholderWidget)

export function registerWidget(type: string, component: WidgetComponent): void {
  register(type, component)
}

export function resolveWidgetComponent(type: string): WidgetComponent {
  return registry.get(type.toLowerCase()) ?? PlaceholderWidget
}

export function hasWidgetType(type: string): boolean {
  return registry.has(type.toLowerCase())
}

export function listRegisteredWidgetTypes(): string[] {
  return Array.from(registry.keys())
}

export function renderDashboardWidget(props: DashboardWidgetComponentProps) {
  return createElement(resolveWidgetComponent(props.widgetType), props)
}
