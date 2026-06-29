import type { NormalizedTableColumn } from '../types'
import type { TableRow } from '@/api/types/tables'

export interface CellRendererProps {
  column: NormalizedTableColumn
  row: TableRow
  value: unknown
}
