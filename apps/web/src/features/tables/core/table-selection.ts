import type { TableRow } from '@/api/types/tables'

export function getRowSelectionKey(row: TableRow, index: number): string {
  return row.public_id ?? `row-${index}`
}

export function toggleRowSelection(
  selection: Set<string>,
  rowKey: string,
): Set<string> {
  const next = new Set(selection)

  if (next.has(rowKey)) {
    next.delete(rowKey)
  } else {
    next.add(rowKey)
  }

  return next
}

export function toggleAllVisibleRows(
  selection: Set<string>,
  rowKeys: string[],
  selectAll: boolean,
): Set<string> {
  const next = new Set(selection)

  rowKeys.forEach((rowKey) => {
    if (selectAll) {
      next.add(rowKey)
    } else {
      next.delete(rowKey)
    }
  })

  return next
}

export function clearRowSelection(): Set<string> {
  return new Set()
}

export function isAllVisibleSelected(
  selection: Set<string>,
  rowKeys: string[],
): boolean {
  if (rowKeys.length === 0) {
    return false
  }

  return rowKeys.every((rowKey) => selection.has(rowKey))
}
