import type { TableFilter } from '@/api/types/tables'

export type FilterOperator =
  | 'equals'
  | 'not_equals'
  | 'contains'
  | 'not_contains'
  | 'greater_than'
  | 'greater_than_or_equal'
  | 'less_than'
  | 'less_than_or_equal'
  | 'between'
  | 'in'
  | 'not_in'
  | 'is_empty'
  | 'is_not_empty'

export function normalizeFilterOperator(operator: string): FilterOperator {
  const normalized = operator.toLowerCase().replace(/\s+/g, '_')

  switch (normalized) {
    case 'eq':
    case 'equals':
      return 'equals'
    case 'neq':
    case 'not_equals':
      return 'not_equals'
    case 'contains':
      return 'contains'
    case 'not_contains':
      return 'not_contains'
    case 'gt':
    case 'greater_than':
      return 'greater_than'
    case 'gte':
    case 'greater_than_or_equal':
      return 'greater_than_or_equal'
    case 'lt':
    case 'less_than':
      return 'less_than'
    case 'lte':
    case 'less_than_or_equal':
      return 'less_than_or_equal'
    case 'between':
      return 'between'
    case 'in':
      return 'in'
    case 'not_in':
      return 'not_in'
    case 'is_empty':
    case 'empty':
      return 'is_empty'
    case 'is_not_empty':
    case 'not_empty':
      return 'is_not_empty'
    default:
      return 'equals'
  }
}

export function serializeFilter(filter: TableFilter): TableFilter {
  return {
    column_key: filter.column_key,
    operator: normalizeFilterOperator(filter.operator),
    value: filter.value,
    metadata: filter.metadata,
  }
}

export function serializeFilters(filters: TableFilter[]): TableFilter[] {
  return filters.map(serializeFilter)
}

export function upsertFilter(
  filters: TableFilter[],
  filter: TableFilter,
): TableFilter[] {
  const next = filters.filter((item) => item.column_key !== filter.column_key)
  return [...next, serializeFilter(filter)]
}

export function removeFilter(filters: TableFilter[], columnKey: string): TableFilter[] {
  return filters.filter((filter) => filter.column_key !== columnKey)
}

export function isFilterActive(filter: TableFilter): boolean {
  if (filter.operator === 'is_empty' || filter.operator === 'is_not_empty') {
    return true
  }

  return filter.value !== undefined && filter.value !== null && filter.value !== ''
}

export function countActiveFilters(filters: TableFilter[]): number {
  return filters.filter(isFilterActive).length
}
