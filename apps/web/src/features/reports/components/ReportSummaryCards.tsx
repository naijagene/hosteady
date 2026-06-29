import type { ReportMetric } from '@/api/types/reports'
import { buildReportMetricDisplay } from '../core/report-metrics'
import { ReportMetricCard } from './ReportMetricCard'

interface ReportSummaryCardsProps {
  metrics: ReportMetric[]
  title?: string
}

export function ReportSummaryCards({ metrics, title }: ReportSummaryCardsProps) {
  if (metrics.length === 0) {
    return null
  }

  return (
    <section className="space-y-3" data-testid="report-summary-cards" aria-label={title ?? 'Report summary'}>
      {title ? <h3 className="text-sm font-medium text-foreground">{title}</h3> : null}
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {metrics.map((metric) => {
          const display = buildReportMetricDisplay(metric)
          return (
            <ReportMetricCard
              key={metric.metric_key}
              title={display.title}
              value={display.value}
              prefix={display.prefix}
              suffix={display.suffix}
              trend={display.trend}
              comparison={display.comparison}
              status={display.status}
              empty={display.empty}
            />
          )
        })}
      </div>
    </section>
  )
}
