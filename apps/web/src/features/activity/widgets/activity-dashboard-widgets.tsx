import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { ActivityFeedWidget } from './ActivityFeedWidget'
import { AuditSummaryWidget } from './AuditSummaryWidget'
import { SystemHistoryWidget } from './SystemHistoryWidget'

export function ActivityFeedDashboardWidget({ widget }: DashboardWidgetComponentProps) {
  return <ActivityFeedWidget title={widget.label ?? 'Recent activity'} binding={{ mode: 'compact', per_page: 6 }} />
}

export function AuditSummaryDashboardWidget() {
  return <AuditSummaryWidget />
}

export function SystemHistoryDashboardWidget() {
  return <SystemHistoryWidget />
}
