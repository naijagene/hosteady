import type { TableView } from '@/api/types/tables'

interface TableViewSelectorProps {
  views: TableView[]
  selectedView: string | null
  onChange: (viewKey: string | null) => void
}

export function TableViewSelector({
  views,
  selectedView,
  onChange,
}: TableViewSelectorProps) {
  if (views.length === 0) {
    return null
  }

  return (
    <label className="flex items-center gap-2 text-xs text-muted-foreground">
      <span>View</span>
      <select
        className="rounded-md border border-border bg-background px-2 py-1 text-foreground"
        value={selectedView ?? ''}
        onChange={(event) => onChange(event.target.value || null)}
      >
        {views.map((view) => (
          <option key={view.view_key} value={view.view_key}>
            {view.label}
          </option>
        ))}
      </select>
    </label>
  )
}
