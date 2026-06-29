import { DashboardMetricCard } from '../components/DashboardMetricCard'
import { buildMetricDisplay } from '../core/dashboard-metrics'
import type { DashboardWidgetComponentProps } from './types'

export function MetricWidget({ widget }: DashboardWidgetComponentProps) {
  const display = buildMetricDisplay(widget.metric, widget.data, widget.label)

  return (
    <DashboardMetricCard
      title={display.title}
      value={display.value}
      prefix={display.prefix}
      suffix={display.suffix}
      trend={display.trend}
      comparison={display.comparison}
      status={display.status}
      icon={display.icon}
      empty={display.empty}
    />
  )
}
