import type { ReportChart } from '@/api/types/reports'

const supportedChartTypes = new Set([
  'line',
  'bar',
  'pie',
  'donut',
  'area',
  'scatter',
  'radar',
  'custom',
])

export function isSupportedChartType(chartType: string): boolean {
  return supportedChartTypes.has(chartType.toLowerCase())
}

export function getChartDatasetSummary(chart: ReportChart): string {
  const pointCount = chart.datasets?.reduce(
    (total, dataset) => total + (Array.isArray(dataset.data) ? dataset.data.length : 0),
    0,
  )

  return `${chart.labels?.length ?? 0} labels · ${pointCount ?? 0} points`
}
