export function NotificationLoadingState() {
  return (
    <div
      className="text-sm text-muted-foreground"
      data-testid="notification-loading-state"
      role="status"
      aria-busy="true"
    >
      Loading notifications…
    </div>
  )
}
