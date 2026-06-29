import type { CellRendererProps } from './types'

export function UnsupportedCell({ column }: CellRendererProps) {
  return (
    <span className="text-xs text-muted-foreground" data-testid="unsupported-cell">
      {column.column_type}
    </span>
  )
}
