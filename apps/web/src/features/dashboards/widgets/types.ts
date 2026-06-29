import type { ResolvedDashboardWidget } from '../types'

export interface DashboardWidgetComponentProps {
  widget: ResolvedDashboardWidget
  widgetType: string
  collapsed?: boolean
}
