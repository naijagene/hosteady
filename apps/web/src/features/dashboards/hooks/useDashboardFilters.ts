import { useCallback, useMemo, useState } from 'react'
import type { DashboardFilter } from '@/api/types/dashboards'
import {
  createInitialFilterValues,
  getFilterValueKey,
  serializeDashboardFilters,
} from '../core/dashboard-filters'

export function useDashboardFilters(filters: DashboardFilter[] = []) {
  const [values, setValues] = useState<Record<string, unknown>>(() =>
    createInitialFilterValues(filters),
  )

  const setFilterValue = useCallback((filterKey: string, value: unknown) => {
    setValues((current) => ({ ...current, [filterKey]: value }))
  }, [])

  const clearFilters = useCallback(() => {
    setValues(createInitialFilterValues(filters))
  }, [filters])

  const serializedFilters = useMemo(
    () => serializeDashboardFilters(filters, values),
    [filters, values],
  )

  return {
    values,
    setFilterValue,
    clearFilters,
    serializedFilters,
    getFilterValueKey,
  }
}
