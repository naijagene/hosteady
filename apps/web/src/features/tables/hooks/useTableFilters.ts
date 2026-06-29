import { useCallback, useState } from 'react'
import type { TableFilter } from '@/api/types/tables'
import { removeFilter, upsertFilter } from '../core/table-filters'
import type { TableQueryState } from '../types'

export function useTableFilters(initial: TableFilter[] = []) {
  const [filters, setFilters] = useState<TableFilter[]>(initial)

  const setFilter = useCallback((filter: TableFilter) => {
    setFilters((current) => upsertFilter(current, filter))
  }, [])

  const clearFilter = useCallback((columnKey: string) => {
    setFilters((current) => removeFilter(current, columnKey))
  }, [])

  const clearAllFilters = useCallback(() => {
    setFilters([])
  }, [])

  const applyToQueryState = useCallback(
    (state: TableQueryState): TableQueryState => ({
      ...state,
      page: 1,
      filters,
    }),
    [filters],
  )

  return {
    filters,
    setFilter,
    clearFilter,
    clearAllFilters,
    applyToQueryState,
  }
}
