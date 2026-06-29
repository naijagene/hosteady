import type { TableSort } from '@/api/types/tables'
import type { NormalizedTableColumn } from '../types'
import { ariaSortValue, getColumnSortDirection } from '../core/table-sorts'
import { TableSortIndicator } from './TableSortIndicator'

interface TableColumnHeaderProps {
  column: NormalizedTableColumn
  sorts: TableSort[]
  onSort?: (columnKey: string) => void
}

export function TableColumnHeader({ column, sorts, onSort }: TableColumnHeaderProps) {
  const direction = getColumnSortDirection(sorts, column.column_key)
  const sortable = column.sortable !== false

  return (
    <th
      scope="col"
      className="px-4 py-2 text-left text-xs font-medium text-muted-foreground"
      aria-sort={sortable ? ariaSortValue(direction) : undefined}
    >
      {sortable ? (
        <button
          type="button"
          className="inline-flex items-center gap-1 hover:text-foreground"
          onClick={() => onSort?.(column.column_key)}
        >
          {column.label}
          <TableSortIndicator direction={direction} />
        </button>
      ) : (
        column.label
      )}
    </th>
  )
}
