import { useCallback, useMemo, useState } from 'react'
import type { TableRow } from '@/api/types/tables'
import {
  clearRowSelection,
  getRowSelectionKey,
  isAllVisibleSelected,
  toggleAllVisibleRows,
  toggleRowSelection,
} from '../core/table-selection'

export function useTableSelection(rows: TableRow[]) {
  const [selection, setSelection] = useState<Set<string>>(new Set())

  const rowKeys = useMemo(
    () => rows.map((row, index) => getRowSelectionKey(row, index)),
    [rows],
  )

  const toggleRow = useCallback((rowKey: string) => {
    setSelection((current) => toggleRowSelection(current, rowKey))
  }, [])

  const toggleAll = useCallback(
    (checked: boolean) => {
      setSelection((current) => toggleAllVisibleRows(current, rowKeys, checked))
    },
    [rowKeys],
  )

  const clearSelection = useCallback(() => {
    setSelection(clearRowSelection())
  }, [])

  return {
    selection,
    selectedCount: selection.size,
    toggleRow,
    toggleAll,
    clearSelection,
    isRowSelected: (rowKey: string) => selection.has(rowKey),
    isAllSelected: isAllVisibleSelected(selection, rowKeys),
  }
}
