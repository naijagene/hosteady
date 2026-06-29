import type { DashboardFilter } from '@/api/types/dashboards'
import { getFilterValueKey } from '../core/dashboard-filters'

interface DashboardFilterBarProps {
  filters: DashboardFilter[]
  values: Record<string, unknown>
  onChange: (filterKey: string, value: unknown) => void
  onClear: () => void
}

export function DashboardFilterBar({
  filters,
  values,
  onChange,
  onClear,
}: DashboardFilterBarProps) {
  if (filters.length === 0) {
    return null
  }

  return (
    <div
      className="flex flex-wrap items-end gap-3 border-b border-border px-4 py-3"
      data-testid="dashboard-filter-bar"
      aria-label="Dashboard filters"
    >
      {filters.map((filter) => {
        const key = getFilterValueKey(filter)
        const filterType = filter.filter_type.toLowerCase()

        return (
          <label key={key} className="flex flex-col gap-1 text-xs text-muted-foreground">
            <span>{filter.label}</span>
            {filterType === 'boolean' ? (
              <input
                type="checkbox"
                checked={Boolean(values[key])}
                onChange={(event) => onChange(key, event.target.checked)}
              />
            ) : filterType === 'select' ? (
              <select
                className="rounded-md border border-border bg-background px-2 py-1 text-foreground"
                value={String(values[key] ?? '')}
                onChange={(event) => onChange(key, event.target.value)}
              >
                <option value="">All</option>
                {(filter.options ?? []).map((option, index) => (
                  <option key={index} value={String(option.value ?? option.label ?? index)}>
                    {String(option.label ?? option.value ?? `Option ${index + 1}`)}
                  </option>
                ))}
              </select>
            ) : (
              <input
                type={filterType === 'date' || filterType === 'date_range' ? 'date' : 'text'}
                className="rounded-md border border-border bg-background px-2 py-1 text-foreground"
                value={String(values[key] ?? '')}
                onChange={(event) => onChange(key, event.target.value)}
              />
            )}
          </label>
        )
      })}
      <button
        type="button"
        className="rounded-md border border-border px-3 py-1 text-xs text-foreground"
        onClick={onClear}
      >
        Clear filters
      </button>
    </div>
  )
}
