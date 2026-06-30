interface ActivityEmptyStateProps {
  title?: string
  message?: string
}

export function ActivityEmptyState({
  title = 'No activity yet',
  message = 'Activity will appear here when audit and history endpoints return data.',
}: ActivityEmptyStateProps) {
  return (
    <div className="px-4 py-10 text-center text-sm text-muted-foreground" data-testid="activity-empty-state">
      <p className="font-medium text-foreground">{title}</p>
      <p className="mt-1 text-xs">{message}</p>
    </div>
  )
}
