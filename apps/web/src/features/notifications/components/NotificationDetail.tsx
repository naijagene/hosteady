import type { Notification } from '@/api/types/notifications'
import { getNotificationActionLabel, resolveNotificationLink } from '../core/notification-actions'
import { formatNotificationDate, getNotificationDisplayTitle } from '../core/notification-normalizer'
import { getNotificationPriorityLabel } from '../core/notification-priority'

interface NotificationDetailProps {
  notification: Notification | null
  actionsEnabled?: boolean
  onMarkRead?: () => Promise<void>
  onMarkUnread?: () => Promise<void>
  onDelete?: () => Promise<void>
  deleteEnabled?: boolean
}

export function NotificationDetail({
  notification,
  actionsEnabled = true,
  onMarkRead,
  onMarkUnread,
  onDelete,
  deleteEnabled = false,
}: NotificationDetailProps) {
  if (!notification) {
    return null
  }

  const link = resolveNotificationLink(notification)
  const unread = !notification.read_at

  return (
    <section className="space-y-4" data-testid="notification-detail">
      <div>
        <p className="text-xs uppercase tracking-wide text-muted-foreground">{notification.category}</p>
        <h2 className="text-lg font-semibold text-foreground">{getNotificationDisplayTitle(notification)}</h2>
        <p className="mt-2 text-sm text-muted-foreground">{notification.body}</p>
      </div>

      <dl className="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
        <div>
          <dt className="font-medium text-foreground">Timestamp</dt>
          <dd>{formatNotificationDate(notification.created_at)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Priority</dt>
          <dd>{getNotificationPriorityLabel(notification.priority)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Sender</dt>
          <dd>{notification.sender ?? '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Recipient</dt>
          <dd>{notification.recipient ?? '—'}</dd>
        </div>
      </dl>

      {notification.attachments && notification.attachments.length > 0 ? (
        <section>
          <h3 className="text-sm font-medium text-foreground">Attachments</h3>
          <ul className="mt-2 space-y-1 text-xs text-muted-foreground">
            {notification.attachments.map((attachment) => (
              <li key={attachment.public_id ?? attachment.title}>{attachment.title ?? attachment.public_id}</li>
            ))}
          </ul>
        </section>
      ) : null}

      <section>
        <h3 className="text-sm font-medium text-foreground">Related records</h3>
        <ul className="mt-2 space-y-1 text-xs text-muted-foreground">
          {notification.metadata?.workflow_instance_public_id ? (
            <li>Workflow instance: {String(notification.metadata.workflow_instance_public_id)}</li>
          ) : null}
          {notification.metadata?.document_public_id ? (
            <li>Document: {String(notification.metadata.document_public_id)}</li>
          ) : null}
          {notification.metadata?.report_key ? <li>Report: {String(notification.metadata.report_key)}</li> : null}
          {notification.metadata?.dashboard_key ? (
            <li>Dashboard: {String(notification.metadata.dashboard_key)}</li>
          ) : null}
        </ul>
      </section>

      <div className="flex flex-wrap gap-2">
        <a href={link ?? `/notifications/${notification.public_id}`} className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground">
          {getNotificationActionLabel(notification)}
        </a>
        {actionsEnabled && unread && onMarkRead ? (
          <button type="button" className="rounded-md border border-border px-3 py-1.5 text-xs font-medium" onClick={() => onMarkRead()}>
            Mark read
          </button>
        ) : null}
        {actionsEnabled && !unread && onMarkUnread ? (
          <button type="button" className="rounded-md border border-border px-3 py-1.5 text-xs font-medium" onClick={() => onMarkUnread()}>
            Mark unread
          </button>
        ) : null}
        {deleteEnabled && onDelete ? (
          <button
            type="button"
            className="rounded-md border border-destructive px-3 py-1.5 text-xs font-medium text-destructive"
            onClick={() => onDelete()}
          >
            Delete
          </button>
        ) : null}
      </div>
    </section>
  )
}
