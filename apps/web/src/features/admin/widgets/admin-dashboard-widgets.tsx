import type { DashboardWidgetComponentProps } from '@/features/dashboards/widgets/types'
import { PlatformStatusWidget } from './PlatformStatusWidget'
import { RuntimeStatusWidget } from './RuntimeStatusWidget'
import { FeatureSummaryWidget } from './FeatureSummaryWidget'
import { OrganizationSummaryWidget } from './OrganizationSummaryWidget'

export function PlatformStatusDashboardWidget(props: DashboardWidgetComponentProps) {
  void props
  return <PlatformStatusWidget />
}

export function RuntimeStatusDashboardWidget(props: DashboardWidgetComponentProps) {
  void props
  return <RuntimeStatusWidget />
}

export function FeatureSummaryDashboardWidget(props: DashboardWidgetComponentProps) {
  void props
  return <FeatureSummaryWidget />
}

export function OrganizationSummaryDashboardWidget(props: DashboardWidgetComponentProps) {
  void props
  return <OrganizationSummaryWidget />
}
