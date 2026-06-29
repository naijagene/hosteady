import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'
import { fetchNotification } from '@/api/endpoints/notifications'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { NotificationDetail } from '../components/NotificationDetail'
import { NotificationErrorState } from '../components/NotificationErrorState'
import { NotificationLoadingState } from '../components/NotificationLoadingState'
import { useNotificationActions } from '../hooks/useNotificationActions'
import { canDeleteNotifications, canManageNotifications, canReadNotifications } from '../core/notification-permissions'

export function NotificationDetailPage() {
  const params = useParams({ strict: false })
  const publicId = typeof params.publicId === 'string' ? params.publicId : null
  const runtime = useHydratedRuntime()
  const permissions = runtime?.permissions ?? []
  const actions = useNotificationActions()

  const query = useQuery({
    queryKey: ['notification', publicId],
    queryFn: () => fetchNotification(publicId!),
    enabled: Boolean(publicId),
  })

  if (!canReadNotifications(permissions)) {
    return <NotificationErrorState message="You do not have permission to view notifications." />
  }

  if (query.isLoading) {
    return <NotificationLoadingState />
  }

  if (query.error) {
    return <NotificationErrorState message="Unable to load notification detail." />
  }

  return (
    <div className="mx-auto w-full max-w-3xl rounded-lg border border-border bg-card p-5">
      <NotificationDetail
        notification={query.data ?? null}
        actionsEnabled={canManageNotifications(permissions)}
        deleteEnabled={canDeleteNotifications(permissions)}
        onMarkRead={query.data ? async () => { await actions.markRead(query.data!.public_id) } : undefined}
        onMarkUnread={query.data ? async () => { await actions.markUnread(query.data!.public_id) } : undefined}
        onDelete={query.data ? async () => { await actions.deleteNotification(query.data!.public_id) } : undefined}
      />
    </div>
  )
}
