interface DashboardErrorStateProps {
  message: string
}

export function DashboardErrorState({ message }: DashboardErrorStateProps) {
  return (
    <div
      className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="dashboard-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
