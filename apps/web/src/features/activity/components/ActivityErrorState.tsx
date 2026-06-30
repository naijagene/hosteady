interface ActivityErrorStateProps {
  message?: string
}

export function ActivityErrorState({ message = 'Unable to load activity.' }: ActivityErrorStateProps) {
  return (
    <div className="px-4 py-8 text-sm text-destructive" role="alert" data-testid="activity-error-state">
      {message}
    </div>
  )
}
