import type { TableAction } from '@/api/types/tables'

interface TableRowActionsProps {
  actions: TableAction[]
  onAction: (action: TableAction) => void
}

export function TableRowActions({ actions, onAction }: TableRowActionsProps) {
  if (actions.length === 0) {
    return null
  }

  return (
    <div className="flex items-center gap-1">
      {actions.map((action) => (
        <button
          key={action.action_key}
          type="button"
          className="rounded-md border border-border px-2 py-0.5 text-xs text-foreground hover:bg-muted"
          aria-label={action.label}
          onClick={() => onAction(action)}
        >
          {action.label}
        </button>
      ))}
    </div>
  )
}
