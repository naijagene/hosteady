interface NotificationToolbarProps {
  onRefresh?: () => void
  onMarkAllRead?: () => Promise<void>
  actionsEnabled?: boolean
  isMarkingAllRead?: boolean
  markAllReadError?: { message: string } | null
}

export function NotificationToolbar({
  onRefresh,
  onMarkAllRead,
  actionsEnabled = true,
  isMarkingAllRead = false,
  markAllReadError,
}: NotificationToolbarProps) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3" data-testid="notification-toolbar">
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => onRefresh?.()}
          aria-label="Refresh notifications"
        >
          Refresh
        </button>
        {actionsEnabled && onMarkAllRead ? (
          <button
            type="button"
            className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
            disabled={isMarkingAllRead}
            onClick={() => onMarkAllRead()}
            aria-busy={isMarkingAllRead}
          >
            {isMarkingAllRead ? 'Marking all…' : 'Mark all read'}
          </button>
        ) : null}
      </div>
      {markAllReadError ? (
        <p className="text-xs text-destructive" role="alert">
          {markAllReadError.message}
        </p>
      ) : null}
    </div>
  )
}
