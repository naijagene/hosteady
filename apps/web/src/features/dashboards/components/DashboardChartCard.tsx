import type { DashboardDataset } from '@/api/types/dashboards'

const supportedChartTypes = new Set([
  'line',
  'bar',
  'pie',
  'donut',
  'area',
  'scatter',
  'radar',
  'table',
  'number',
  'custom',
])

interface DashboardChartCardProps {
  title: string
  chartType: string
  labels?: string[]
  datasets?: DashboardDataset[]
  summary?: string
}

export function DashboardChartCard({
  title,
  chartType,
  labels = [],
  datasets = [],
  summary,
}: DashboardChartCardProps) {
  const supported = supportedChartTypes.has(chartType.toLowerCase())
  const pointCount = datasets.reduce(
    (total, dataset) => total + (dataset.data?.length ?? 0),
    0,
  )

  return (
    <div className="space-y-3" data-testid="dashboard-chart-card" aria-label={`${title} chart`}>
      <div>
        <h4 className="text-sm font-medium text-foreground">{title}</h4>
        <p className="text-xs text-muted-foreground">Chart type: {chartType}</p>
      </div>
      <div className="flex h-32 items-end gap-2 rounded-md border border-dashed border-border bg-muted/20 p-3">
        {labels.length === 0 && pointCount === 0 ? (
          <p className="text-xs text-muted-foreground">No chart data available</p>
        ) : (
          labels.slice(0, 6).map((label, index) => (
            <div key={label} className="flex flex-1 flex-col items-center gap-1">
              <div
                className="w-full rounded-sm bg-primary/30"
                style={{ height: `${Math.max(16, ((index + 1) / 6) * 100)}%` }}
                aria-hidden
              />
              <span className="truncate text-[10px] text-muted-foreground">{label}</span>
            </div>
          ))
        )}
      </div>
      {summary ? <p className="text-xs text-muted-foreground">{summary}</p> : null}
      {!supported ? (
        <p className="text-xs text-muted-foreground">Unsupported chart type fallback active.</p>
      ) : null}
    </div>
  )
}
