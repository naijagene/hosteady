import type { Notification } from '@/api/types/notifications'
import { formatNotificationDate, getNotificationDisplayTitle, isNotificationUnread } from '../core/notification-normalizer'
import { getNotificationPriorityLabel, getNotificationPriorityTone } from '../core/notification-priority'
import { getNotificationIconLabel } from '../core/notification-icons'

interface NotificationCardProps {
  notification: Notification
  onOpen?: (notification: Notification) => void
  onMarkRead?: (notification: Notification) => void
  onMarkUnread?: (notification: Notification) => void
  onDelete?: (notification: Notification) => void
  actionsEnabled?: boolean
  deleteEnabled?: boolean
}

const priorityClasses = {
  default: 'bg-muted text-muted-foreground',
  warning: 'bg-amber-100 text-amber-800',
  danger: 'bg-red-100 text-red-800',
}

export function NotificationCard({
  notification,
  onOpen,
  onMarkRead,
  onMarkUnread,
  onDelete,
  actionsEnabled = true,
  deleteEnabled = false,
}: NotificationCardProps) {
  const unread = isNotificationUnread(notification)
  const priorityTone = getNotificationPriorityTone(notification.priority)

  return (
    <article
      className={`rounded-lg border bg-card p-4 ${unread ? 'border-primary/40' : 'border-border'}`}
      data-testid={`notification-card-${notification.public_id}`}
      aria-label={`Notification ${getNotificationDisplayTitle(notification)}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-xs uppercase tracking-wide text-muted-foreground">
            {getNotificationIconLabel(notification.category)}
          </p>
          <h3 className="text-sm font-medium text-foreground">{getNotificationDisplayTitle(notification)}</h3>
          <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">{notification.body}</p>
        </div>
        <span className={`rounded-full px-2 py-0.5 text-[10px] font-medium capitalize ${priorityClasses[priorityTone]}`}>
          {getNotificationPriorityLabel(notification.priority)}
        </span>
      </div>

      <p className="mt-2 text-xs text-muted-foreground">{formatNotificationDate(notification.created_at)}</p>

      <div className="mt-3 flex flex-wrap gap-2">
        {onOpen ? (
          <button
            type="button"
            className="rounded-md border border-border px-3 py-1.5 text-xs font-medium"
            onClick={() => onOpen(notification)}
          >
            View
          </button>
        ) : null}
        {actionsEnabled && unread && onMarkRead ? (
          <button
            type="button"
            className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
            onClick={() => onMarkRead(notification)}
          >
            Mark read
          </button>
        ) : null}
        {actionsEnabled && !unread && onMarkUnread ? (
          <button
            type="button"
            className="rounded-md border border-border px-3 py-1.5 text-xs font-medium"
            onClick={() => onMarkUnread(notification)}
          >
            Mark unread
          </button>
        ) : null}
        {deleteEnabled && onDelete ? (
          <button
            type="button"
            className="rounded-md border border-destructive px-3 py-1.5 text-xs font-medium text-destructive"
            onClick={() => onDelete(notification)}
          >
            Delete
          </button>
        ) : null}
      </div>
    </article>
  )
}
