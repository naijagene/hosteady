import { useQuery } from '@tanstack/react-query'
import { Link, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
import { Bell } from '@/components/icons'
import { fetchNotifications } from '@/api/endpoints/notifications'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { NotificationBadge } from './NotificationBadge'
import { NotificationLoadingState } from './NotificationLoadingState'
import { useNotificationActions } from '../hooks/useNotificationActions'
import { canManageNotifications, canReadNotifications } from '../core/notification-permissions'
import { formatNotificationDate, getNotificationDisplayTitle } from '../core/notification-normalizer'
import { resolveNotificationLink } from '../core/notification-actions'

export function NotificationBell() {
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const navigate = useNavigate()
  const actions = useNotificationActions()
  const [open, setOpen] = useState(false)

  const previewQuery = useQuery({
    queryKey: ['notification-bell-preview'],
    queryFn: () => fetchNotifications({ per_page: 5 }),
    enabled: canReadNotifications(permissions),
  })

  const unreadCount =
    previewQuery.data?.filter((notification) => !notification.read_at).length ??
    runtime?.unreadNotificationCount ??
    0

  const canRead = canReadNotifications(permissions)
  const canManage = canManageNotifications(permissions)

  return (
    <div className="relative" data-testid="notification-bell">
      <button
        type="button"
        className="relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-primary-foreground/20 text-primary-foreground hover:bg-primary-foreground/10"
        aria-label={`Notifications${unreadCount > 0 ? `, ${unreadCount} unread` : ''}`}
        aria-expanded={open}
        aria-haspopup="menu"
        onClick={() => setOpen((current) => !current)}
      >
        <Bell className="h-4 w-4" aria-hidden />
        <span className="absolute -right-1 -top-1">
          <NotificationBadge count={unreadCount} />
        </span>
      </button>

      {open ? (
        <div
          className="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-border bg-background p-3 shadow-lg"
          role="menu"
          aria-label="Notification preview"
        >
          <div className="mb-3 flex items-center justify-between gap-2">
            <p className="text-sm font-medium text-foreground">Notifications</p>
            <div className="flex gap-2">
              <button
                type="button"
                className="text-xs text-muted-foreground hover:text-foreground"
                onClick={() => previewQuery.refetch()}
                aria-label="Refresh notifications"
              >
                Refresh
              </button>
              {canManage ? (
                <button
                  type="button"
                  className="text-xs text-primary hover:underline"
                  onClick={() => actions.markAllRead()}
                  aria-label="Mark all notifications read"
                >
                  Mark all read
                </button>
              ) : null}
            </div>
          </div>

          {!canRead ? (
            <p className="text-xs text-muted-foreground">Notifications unavailable.</p>
          ) : previewQuery.isLoading ? (
            <NotificationLoadingState />
          ) : previewQuery.data && previewQuery.data.length > 0 ? (
            <ul className="max-h-64 space-y-2 overflow-y-auto" aria-label="Recent notifications">
              {previewQuery.data.slice(0, 5).map((notification) => (
                <li key={notification.public_id}>
                  <button
                    type="button"
                    className="w-full rounded-md border border-border px-3 py-2 text-left hover:bg-muted/40"
                    role="menuitem"
                    onClick={() => {
                      setOpen(false)
                      if (!notification.read_at && canManage) {
                        void actions.markRead(notification.public_id)
                      }
                      void navigate({ to: resolveNotificationLink(notification) ?? '/notifications' })
                    }}
                  >
                    <p className="text-xs font-medium text-foreground">{getNotificationDisplayTitle(notification)}</p>
                    <p className="text-[10px] text-muted-foreground">{formatNotificationDate(notification.created_at)}</p>
                  </button>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-xs text-muted-foreground">No notifications.</p>
          )}

          <div className="mt-3 border-t border-border pt-3">
            <Link
              to="/notifications"
              className="text-xs font-medium text-primary hover:underline"
              onClick={() => setOpen(false)}
            >
              Open Notification Center
            </Link>
          </div>
        </div>
      ) : null}
    </div>
  )
}
