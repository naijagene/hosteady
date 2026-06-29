import type { ReportChart } from '@/api/types/reports'
import { getChartDatasetSummary, isSupportedChartType } from '../core/report-charts'

interface ReportChartPlaceholderProps {
  chart: ReportChart
}

export function ReportChartPlaceholder({ chart }: ReportChartPlaceholderProps) {
  const chartType = chart.chart_type.toLowerCase()
  const supported = isSupportedChartType(chartType)

  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/10 p-4"
      data-testid="report-chart-placeholder"
      aria-label={`${chart.label} chart placeholder`}
    >
      <div className="flex items-start justify-between gap-2">
        <div>
          <h4 className="text-sm font-medium text-foreground">{chart.label}</h4>
          <p className="text-xs text-muted-foreground">
            {supported ? `${chartType} chart placeholder` : 'Unsupported chart type'}
          </p>
        </div>
        <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-foreground">{chartType}</span>
      </div>
      <p className="mt-3 text-xs text-muted-foreground">{getChartDatasetSummary(chart)}</p>
      <div className="mt-4 flex h-28 items-center justify-center rounded-md border border-border bg-background text-xs text-muted-foreground">
        Chart rendering is not enabled in this milestone.
      </div>
    </div>
  )
}
