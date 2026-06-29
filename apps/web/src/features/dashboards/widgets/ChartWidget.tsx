import { DashboardChartCard } from '../components/DashboardChartCard'
import type { DashboardWidgetComponentProps } from './types'

export function ChartWidget({ widget }: DashboardWidgetComponentProps) {
  const chart = widget.data?.chart
  const chartType = widget.chart_type ?? chart?.type ?? 'line'

  return (
    <DashboardChartCard
      title={widget.label}
      chartType={chartType}
      labels={chart?.labels ?? []}
      datasets={chart?.datasets ?? []}
      summary={widget.data?.metadata?.summary as string | undefined}
    />
  )
}
