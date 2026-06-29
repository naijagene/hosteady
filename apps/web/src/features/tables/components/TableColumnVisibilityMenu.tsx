import type { NormalizedTableColumn } from '../types'

interface TableColumnVisibilityMenuProps {
  columns: NormalizedTableColumn[]
  hiddenColumnKeys: Set<string>
  onToggle: (columnKey: string) => void
}

export function TableColumnVisibilityMenu({
  columns,
  hiddenColumnKeys,
  onToggle,
}: TableColumnVisibilityMenuProps) {
  return (
    <details className="relative" data-testid="table-column-visibility-menu">
      <summary className="cursor-pointer rounded-md border border-border px-3 py-1 text-xs text-foreground">
        Columns
      </summary>
      <div className="absolute z-10 mt-2 min-w-48 rounded-md border border-border bg-card p-2 shadow-sm">
        {columns.map((column) => (
          <label
            key={column.column_key}
            className="flex items-center gap-2 px-2 py-1 text-xs text-foreground"
          >
            <input
              type="checkbox"
              checked={!hiddenColumnKeys.has(column.column_key)}
              onChange={() => onToggle(column.column_key)}
            />
            {column.label}
          </label>
        ))}
      </div>
    </details>
  )
}
