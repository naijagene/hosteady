import { useCallback, useMemo, useState } from 'react'
import type { NormalizedTableDefinition } from '../types'
import { getVisibleColumns } from '../core/table-normalizer'
import { toggleColumnVisibility } from '../core/table-columns'

export function useTableColumns(model: NormalizedTableDefinition) {
  const [hiddenColumnKeys, setHiddenColumnKeys] = useState<Set<string>>(new Set())
  const [selectedView, setSelectedView] = useState<string | null>(
    model.views[0]?.view_key ?? null,
  )

  const visibleColumns = useMemo(
    () => getVisibleColumns(model, hiddenColumnKeys, selectedView),
    [model, hiddenColumnKeys, selectedView],
  )

  const visibleColumnKeys = useMemo(
    () => visibleColumns.map((column) => column.column_key),
    [visibleColumns],
  )

  const toggleColumn = useCallback((columnKey: string) => {
    setHiddenColumnKeys((current) => toggleColumnVisibility(current, columnKey))
  }, [])

  return {
    visibleColumns,
    visibleColumnKeys,
    hiddenColumnKeys,
    selectedView,
    setSelectedView,
    toggleColumn,
  }
}
