import type { TableSort } from '@/api/types/tables'

export type SortDirection = 'asc' | 'desc' | null

export function getColumnSortDirection(
  sorts: TableSort[],
  columnKey: string,
): SortDirection {
  const match = sorts.find((sort) => sort.column_key === columnKey)
  return match?.direction ?? null
}

export function toggleColumnSort(
  sorts: TableSort[],
  columnKey: string,
): TableSort[] {
  const current = getColumnSortDirection(sorts, columnKey)

  if (current === null) {
    return [{ column_key: columnKey, direction: 'asc' }]
  }

  if (current === 'asc') {
    return [{ column_key: columnKey, direction: 'desc' }]
  }

  return []
}

export function ariaSortValue(direction: SortDirection): 'ascending' | 'descending' | 'none' {
  if (direction === 'asc') {
    return 'ascending'
  }

  if (direction === 'desc') {
    return 'descending'
  }

  return 'none'
}

export function serializeSorts(sorts: TableSort[]): TableSort[] {
  return sorts.map((sort) => ({
    column_key: sort.column_key,
    direction: sort.direction === 'desc' ? 'desc' : 'asc',
  }))
}
