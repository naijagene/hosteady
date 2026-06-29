import type { DashboardFilter } from '@/api/types/dashboards'

export function serializeDashboardFilters(
  filters: DashboardFilter[],
  values: Record<string, unknown>,
): DashboardFilter[] {
  return filters.map((filter) => ({
    ...filter,
    value: values[filter.filter_key] ?? filter.value,
  }))
}

export function getFilterValueKey(filter: DashboardFilter): string {
  return filter.filter_key || filter.field_key || filter.label
}

export function createInitialFilterValues(filters: DashboardFilter[]): Record<string, unknown> {
  return filters.reduce<Record<string, unknown>>((accumulator, filter) => {
    accumulator[getFilterValueKey(filter)] = filter.value ?? ''
    return accumulator
  }, {})
}

export function isSupportedFilterType(filterType: string): boolean {
  return ['text', 'select', 'date', 'date_range', 'boolean'].includes(filterType.toLowerCase())
}
