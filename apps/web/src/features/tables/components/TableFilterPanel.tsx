import type { TableFilter } from '@/api/types/tables'
import type { NormalizedTableColumn } from '../types'

interface TableFilterPanelProps {
  columns: NormalizedTableColumn[]
  filters: TableFilter[]
  onApplyFilter: (filter: TableFilter) => void
  onClearFilter: (columnKey: string) => void
}

export function TableFilterPanel({
  columns,
  filters,
  onApplyFilter,
  onClearFilter,
}: TableFilterPanelProps) {
  const filterableColumns = columns.filter((column) => column.filterable !== false)

  if (filterableColumns.length === 0) {
    return null
  }

  return (
    <div className="flex flex-wrap gap-2" data-testid="table-filter-panel">
      {filterableColumns.map((column) => {
        const active = filters.find((filter) => filter.column_key === column.column_key)

        return (
          <div key={column.column_key} className="flex items-center gap-1">
            <label className="sr-only" htmlFor={`filter-${column.column_key}`}>
              Filter {column.label}
            </label>
            <input
              id={`filter-${column.column_key}`}
              type="text"
              placeholder={`Filter ${column.label}`}
              className="rounded-md border border-border bg-background px-2 py-1 text-xs text-foreground"
              value={active?.value === undefined || active?.value === null ? '' : String(active.value)}
              onChange={(event) =>
                onApplyFilter({
                  column_key: column.column_key,
                  operator: 'contains',
                  value: event.target.value,
                })
              }
            />
            {active ? (
              <button
                type="button"
                className="text-xs text-muted-foreground underline"
                onClick={() => onClearFilter(column.column_key)}
              >
                Clear
              </button>
            ) : null}
          </div>
        )
      })}
    </div>
  )
}
