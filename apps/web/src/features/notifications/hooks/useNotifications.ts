import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchNotifications } from '@/api/endpoints/notifications'
import type { NotificationBindingContext, NotificationQueryPayload } from '@/api/types/notifications'
import {
  createInitialNotificationQuery,
  mergeNotificationQueryPayload,
  queryNotificationsLocally,
} from '../core/notification-query'
import { toNotificationQueryError } from '../core/notification-errors'

export function useNotifications(binding?: NotificationBindingContext) {
  const [queryPayload, setQueryPayload] = useState<NotificationQueryPayload>(() =>
    createInitialNotificationQuery({
      per_page: binding?.per_page ?? 25,
      category: binding?.category,
      metadata: { source: 'web', mode: binding?.mode },
    }),
  )

  const query = useQuery({
    queryKey: ['notifications-query', queryPayload, binding?.mode],
    queryFn: () => fetchNotifications(queryPayload),
  })

  const result = useMemo(() => {
    if (!query.data) {
      return null
    }

    const local = queryNotificationsLocally(query.data, queryPayload)
    return {
      items: local.items,
      page: queryPayload.page ?? 1,
      per_page: queryPayload.per_page ?? 25,
      total: local.total,
      has_more: local.has_more,
    }
  }, [query.data, queryPayload])

  return {
    query,
    queryPayload,
    setQueryPayload,
    updateQueryPayload: (patch: Partial<NotificationQueryPayload>) =>
      setQueryPayload((current) => mergeNotificationQueryPayload(current, patch)),
    result,
    notifications: query.data ?? [],
    error: query.error ? toNotificationQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
