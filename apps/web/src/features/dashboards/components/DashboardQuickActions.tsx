interface DashboardQuickActionsProps {
  title: string
  actions: Array<{ label?: string; action_key?: string }>
}

export function DashboardQuickActions({ title, actions }: DashboardQuickActionsProps) {
  return (
    <div className="space-y-3" data-testid="dashboard-quick-actions" aria-label={`${title} quick actions`}>
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      <div className="flex flex-wrap gap-2">
        {actions.length === 0 ? (
          <p className="text-xs text-muted-foreground">No quick actions configured.</p>
        ) : (
          actions.map((action, index) => (
            <button
              key={action.action_key ?? index}
              type="button"
              className="rounded-md border border-border px-3 py-1 text-xs text-foreground"
              aria-label={action.label ?? 'Quick action'}
            >
              {action.label ?? 'Action'}
            </button>
          ))
        )}
      </div>
    </div>
  )
}
