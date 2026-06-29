import type { ReportMetric } from '@/api/types/reports'

export function formatReportMetricValue(value: unknown, format?: string | null): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  if (format === 'percent') {
    return `${value}%`
  }

  return String(value)
}

export function buildReportMetricDisplay(metric: ReportMetric) {
  const metadata = metric.metadata ?? {}
  return {
    title: metric.label,
    value: formatReportMetricValue(metric.value, metric.format),
    prefix: metric.prefix ?? (typeof metadata.prefix === 'string' ? metadata.prefix : undefined),
    suffix: metric.suffix ?? (typeof metadata.suffix === 'string' ? metadata.suffix : undefined),
    trend: metric.trend ?? (typeof metadata.trend === 'string' ? metadata.trend : undefined),
    comparison:
      metric.comparison ?? (typeof metadata.comparison === 'string' ? metadata.comparison : undefined),
    status: metric.status ?? (typeof metadata.status === 'string' ? metadata.status : undefined),
    empty: metric.value === null || metric.value === undefined || metric.value === '',
  }
}
