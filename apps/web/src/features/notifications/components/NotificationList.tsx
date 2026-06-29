import type { Notification } from '@/api/types/notifications'
import { NotificationCard } from './NotificationCard'
import { NotificationEmptyState } from './NotificationEmptyState'

interface NotificationListProps {
  notifications: Notification[]
  onOpen?: (notification: Notification) => void
  onMarkRead?: (notification: Notification) => void
  onMarkUnread?: (notification: Notification) => void
  onDelete?: (notification: Notification) => void
  actionsEnabled?: boolean
  deleteEnabled?: boolean
  emptyMessage?: string
}

export function NotificationList({
  notifications,
  onOpen,
  onMarkRead,
  onMarkUnread,
  onDelete,
  actionsEnabled,
  deleteEnabled,
  emptyMessage,
}: NotificationListProps) {
  if (notifications.length === 0) {
    return <NotificationEmptyState message={emptyMessage} />
  }

  return (
    <div className="grid gap-3" data-testid="notification-list">
      {notifications.map((notification) => (
        <NotificationCard
          key={notification.public_id}
          notification={notification}
          onOpen={onOpen}
          onMarkRead={onMarkRead}
          onMarkUnread={onMarkUnread}
          onDelete={onDelete}
          actionsEnabled={actionsEnabled}
          deleteEnabled={deleteEnabled}
        />
      ))}
    </div>
  )
}
