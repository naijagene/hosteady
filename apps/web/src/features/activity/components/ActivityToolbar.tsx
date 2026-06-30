import { ActivitySearchBox } from './ActivitySearchBox'

interface ActivityToolbarProps {
  search: string
  onSearchChange: (value: string) => void
  onRefresh?: () => void
  source?: string
}

export function ActivityToolbar({ search, onSearchChange, onRefresh, source }: ActivityToolbarProps) {
  return (
    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between" data-testid="activity-toolbar">
      <ActivitySearchBox value={search} onChange={onSearchChange} />
      <div className="flex items-center gap-2">
        {source ? <span className="text-xs text-muted-foreground">Source: {source}</span> : null}
        <button
          type="button"
          aria-label="Refresh activity"
          className="rounded-md border border-border px-3 py-2 text-sm hover:bg-muted"
          onClick={onRefresh}
        >
          Refresh
        </button>
      </div>
    </div>
  )
}
