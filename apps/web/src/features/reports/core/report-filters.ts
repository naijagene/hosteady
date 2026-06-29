import type { ReportFilter } from '@/api/types/reports'

export function isSupportedReportFilterType(filterType: string): boolean {
  return ['text', 'select', 'date', 'date_range', 'boolean'].includes(filterType.toLowerCase())
}

export function serializeReportFilters(
  filters: ReportFilter[],
  values: Record<string, unknown>,
): ReportFilter[] {
  return filters.map((filter) => ({
    ...filter,
    value: values[filter.filter_key] ?? filter.value,
  }))
}
