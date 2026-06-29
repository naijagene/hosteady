interface DashboardEmptyStateProps {
  message?: string
}

export function DashboardEmptyState({
  message = 'No dashboard widgets configured.',
}: DashboardEmptyStateProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground"
      data-testid="dashboard-empty-state"
    >
      {message}
    </div>
  )
}
