import type { DashboardMetric, DashboardWidgetData } from '@/api/types/dashboards'

export interface NormalizedMetricDisplay {
  title: string
  value: string
  prefix?: string
  suffix?: string
  trend?: string | null
  comparison?: string | null
  status?: string | null
  icon?: string | null
  empty: boolean
}

export function formatMetricValue(value: unknown, format = 'number'): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  if (format === 'money') {
    return String(value)
  }

  if (format === 'percent') {
    return `${value}%`
  }

  return String(value)
}

export function buildMetricDisplay(
  metric: DashboardMetric | null | undefined,
  data: DashboardWidgetData | null | undefined,
  fallbackTitle: string,
): NormalizedMetricDisplay {
  const metadata = {
    ...metric?.metadata,
    ...data?.metadata,
  }

  const value = data?.value ?? metadata.value
  const format = metric?.format ?? asString(metadata.format, 'number')

  return {
    title: metric?.label ?? asString(metadata.title, fallbackTitle),
    value: formatMetricValue(value, format),
    prefix: asOptionalString(metadata.prefix) ?? undefined,
    suffix: asOptionalString(metadata.suffix) ?? undefined,
    trend: asOptionalString(metadata.trend),
    comparison: asOptionalString(metadata.comparison),
    status: asOptionalString(metadata.status),
    icon: asOptionalString(metadata.icon),
    empty: value === null || value === undefined || value === '',
  }
}

function asString(value: unknown, fallback = ''): string {
  return typeof value === 'string' ? value : fallback
}

function asOptionalString(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value : null
}
