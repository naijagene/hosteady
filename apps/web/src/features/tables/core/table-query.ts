import type { TableBindingContext, TableQueryPayload } from '@/api/types/tables'
import type { TableQueryState } from '../types'

export function buildTableQueryPayload(
  state: TableQueryState,
  options?: {
    binding?: TableBindingContext
    visibleColumnKeys?: string[]
  },
): TableQueryPayload {
  return {
    page: state.page,
    per_page: state.perPage,
    search: state.search.trim() === '' ? null : state.search.trim(),
    filters: state.filters,
    sorts: state.sorts,
    selected_view: state.selectedView,
    columns: options?.visibleColumnKeys,
    metadata: {
      source: options?.binding?.source ?? 'web',
      page: options?.binding?.page,
      binding: options?.binding?.binding,
    },
  }
}

export function createInitialQueryState(options?: {
  perPage?: number
  defaultFilters?: TableQueryState['filters']
  defaultSorts?: TableQueryState['sorts']
  defaultView?: string | null
}): TableQueryState {
  return {
    page: 1,
    perPage: options?.perPage ?? 25,
    search: '',
    filters: options?.defaultFilters ?? [],
    sorts: options?.defaultSorts ?? [],
    selectedView: options?.defaultView ?? null,
  }
}

export function tableQueryKey(
  moduleKey: string,
  tableKey: string,
  state: TableQueryState,
  visibleColumnKeys: string[],
) {
  return [
    'table-query',
    moduleKey,
    tableKey,
    state.page,
    state.perPage,
    state.search,
    state.filters,
    state.sorts,
    state.selectedView,
    visibleColumnKeys,
  ] as const
}
