import type { NormalizedTableColumn } from '../types'
import type { TableRow } from '@/api/types/tables'
import { renderTableCell } from '../cells'

interface TableCellRendererProps {
  column: NormalizedTableColumn
  row: TableRow
}

export function TableCellRenderer({ column, row }: TableCellRendererProps) {
  const value = row.values[column.column_key] ?? row.values[column.column_key.toLowerCase()]

  return (
    <td className="px-4 py-2 text-sm text-foreground">
      {renderTableCell({ column, row, value })}
    </td>
  )
}
