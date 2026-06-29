import type { DashboardAction } from '@/api/types/dashboards'

interface DashboardToolbarProps {
  title: string
  description?: string | null
  actions: DashboardAction[]
  onAction: (action: DashboardAction) => void
  message?: string | null
}

export function DashboardToolbar({
  title,
  description,
  actions,
  onAction,
  message,
}: DashboardToolbarProps) {
  return (
    <header
      className="space-y-3 border-b border-border px-4 py-3"
      data-testid="dashboard-toolbar"
      aria-label="Dashboard toolbar"
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-sm font-semibold text-foreground">{title}</h2>
          {description ? (
            <p className="text-xs text-muted-foreground">{description}</p>
          ) : null}
        </div>
        <div className="flex flex-wrap gap-2">
          {actions.map((action) => (
            <button
              key={action.action_key}
              type="button"
              className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
              aria-label={action.label}
              onClick={() => onAction(action)}
            >
              {action.label}
            </button>
          ))}
        </div>
      </div>
      {message ? (
        <p className="text-xs text-muted-foreground" role="status">
          {message}
        </p>
      ) : null}
    </header>
  )
}
