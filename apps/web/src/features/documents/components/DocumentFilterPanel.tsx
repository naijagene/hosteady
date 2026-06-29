import type { DocumentFilter } from '@/api/types/documents'

interface DocumentFilterPanelProps {
  filters: DocumentFilter[]
  onFilterChange: (filterKey: string, value: string) => void
}

export function DocumentFilterPanel({ filters, onFilterChange }: DocumentFilterPanelProps) {
  if (filters.length === 0) {
    return null
  }

  return (
    <section className="flex flex-wrap gap-2" data-testid="document-filter-panel" aria-label="Document filters">
      {filters.map((filter) => (
        <label key={filter.filter_key} className="text-xs text-muted-foreground">
          {filter.label}
          <input
            type="text"
            aria-label={filter.label}
            value={String(filter.value ?? '')}
            onChange={(event) => onFilterChange(filter.filter_key, event.target.value)}
            className="ml-2 rounded-md border border-border bg-background px-2 py-1 text-sm text-foreground"
          />
        </label>
      ))}
    </section>
  )
}
