interface NotificationErrorStateProps {
  message: string
}

export function NotificationErrorState({ message }: NotificationErrorStateProps) {
  return (
    <div
      className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="notification-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
