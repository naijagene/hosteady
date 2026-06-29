interface ReportExportMenuProps {
  onExport?: (format: 'pdf' | 'xlsx' | 'csv' | 'json') => void
  isExporting?: boolean
  message?: string | null
}

const exportFormats: Array<{ key: 'pdf' | 'xlsx' | 'csv' | 'json'; label: string }> = [
  { key: 'pdf', label: 'PDF' },
  { key: 'xlsx', label: 'Excel' },
  { key: 'csv', label: 'CSV' },
  { key: 'json', label: 'JSON' },
]

export function ReportExportMenu({
  onExport,
  isExporting = false,
  message,
}: ReportExportMenuProps) {
  return (
    <div className="relative" data-testid="report-export-menu">
      <details className="group">
        <summary
          className="cursor-pointer list-none rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
          aria-label="Export report"
        >
          Export
        </summary>
        <div className="absolute right-0 z-10 mt-1 min-w-32 rounded-md border border-border bg-card p-1 shadow-sm">
          {exportFormats.map((format) => (
            <button
              key={format.key}
              type="button"
              className="block w-full rounded px-2 py-1 text-left text-xs text-foreground hover:bg-muted disabled:opacity-50"
              aria-label={`Export as ${format.label}`}
              disabled={isExporting}
              onClick={() => onExport?.(format.key)}
            >
              {format.label}
            </button>
          ))}
        </div>
      </details>
      {message ? (
        <p className="mt-1 text-xs text-muted-foreground" role="status">
          {message}
        </p>
      ) : null}
    </div>
  )
}
