import type { TableAction } from '@/api/types/tables'

interface TableActionBarProps {
  actions: TableAction[]
  selectedCount?: number
  onAction: (action: TableAction) => void
}

export function TableActionBar({
  actions,
  selectedCount = 0,
  onAction,
}: TableActionBarProps) {
  return (
    <div className="flex flex-wrap items-center gap-2" data-testid="table-action-bar">
      {selectedCount > 0 ? (
        <span className="text-xs text-muted-foreground">{selectedCount} selected</span>
      ) : null}
      {actions.map((action) => (
        <button
          key={action.action_key}
          type="button"
          className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
          onClick={() => onAction(action)}
        >
          {action.label}
        </button>
      ))}
      {selectedCount > 0 ? (
        <button
          type="button"
          disabled
          className="rounded-md border border-border px-3 py-1 text-xs text-muted-foreground"
        >
          Bulk action (placeholder)
        </button>
      ) : null}
    </div>
  )
}
