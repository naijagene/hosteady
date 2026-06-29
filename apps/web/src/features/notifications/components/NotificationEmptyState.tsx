interface NotificationEmptyStateProps {
  message?: string
}

export function NotificationEmptyState({
  message = 'No notifications match your current filters.',
}: NotificationEmptyStateProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground"
      data-testid="notification-empty-state"
      role="status"
    >
      {message}
    </div>
  )
}
