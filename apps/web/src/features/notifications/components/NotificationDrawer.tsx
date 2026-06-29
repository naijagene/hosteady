import type { Notification } from '@/api/types/notifications'
import { NotificationDetail } from './NotificationDetail'

interface NotificationDrawerProps {
  notification: Notification | null
  open: boolean
  actionsEnabled?: boolean
  deleteEnabled?: boolean
  onClose: () => void
  onMarkRead?: () => Promise<void>
  onMarkUnread?: () => Promise<void>
  onDelete?: () => Promise<void>
}

export function NotificationDrawer({
  notification,
  open,
  actionsEnabled,
  deleteEnabled,
  onClose,
  onMarkRead,
  onMarkUnread,
  onDelete,
}: NotificationDrawerProps) {
  if (!open || !notification) {
    return null
  }

  return (
    <aside
      className="fixed inset-y-0 right-0 z-40 w-full max-w-xl border-l border-border bg-background p-5 shadow-lg"
      data-testid="notification-drawer"
      role="dialog"
      aria-modal="true"
      aria-label={`Notification detail ${notification.title}`}
    >
      <div className="mb-4 flex items-start justify-between gap-3">
        <h2 className="text-base font-semibold text-foreground">Notification detail</h2>
        <button type="button" className="text-sm text-muted-foreground" onClick={onClose} aria-label="Close notification detail">
          Close
        </button>
      </div>
      <NotificationDetail
        notification={notification}
        actionsEnabled={actionsEnabled}
        deleteEnabled={deleteEnabled}
        onMarkRead={onMarkRead}
        onMarkUnread={onMarkUnread}
        onDelete={onDelete}
      />
    </aside>
  )
}
