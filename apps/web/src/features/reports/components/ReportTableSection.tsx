import type { ReportColumn } from '@/api/types/reports'
import type { MetadataRecord } from '@/api/types/metadata-common'

interface ReportTableSectionProps {
  title: string
  columns: ReportColumn[]
  rows: MetadataRecord[]
  maxVisibleRows?: number
}

function resolveCellValue(row: MetadataRecord, column: ReportColumn): string {
  const value = row[column.column_key] ?? row[column.label]
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return String(value)
}

export function ReportTableSection({
  title,
  columns,
  rows,
  maxVisibleRows = 25,
}: ReportTableSectionProps) {
  const visibleColumns = columns.filter((column) => column.visible !== false)
  const visibleRows = rows.slice(0, maxVisibleRows)
  const hiddenCount = Math.max(rows.length - visibleRows.length, 0)

  if (visibleColumns.length === 0) {
    return (
      <div className="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground">
        No table columns configured.
      </div>
    )
  }

  return (
    <section className="space-y-2" data-testid="report-table-section">
      <h3 className="text-sm font-medium text-foreground">{title}</h3>
      {rows.length === 0 ? (
        <p className="text-sm text-muted-foreground" role="status">
          No rows available for this report section.
        </p>
      ) : (
        <div className="overflow-x-auto rounded-md border border-border">
          <table className="min-w-full text-left text-sm">
            <caption className="sr-only">{title}</caption>
            <thead className="border-b border-border bg-muted/30">
              <tr>
                {visibleColumns.map((column) => (
                  <th key={column.column_key} className="px-3 py-2 font-medium text-foreground">
                    {column.label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {visibleRows.map((row, rowIndex) => (
                <tr key={rowIndex} className="border-b border-border/60">
                  {visibleColumns.map((column) => (
                    <td key={column.column_key} className="px-3 py-2 text-muted-foreground">
                      {resolveCellValue(row, column)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {hiddenCount > 0 ? (
        <p className="text-xs text-muted-foreground">
          Showing {visibleRows.length} of {rows.length} rows.
        </p>
      ) : null}
    </section>
  )
}
