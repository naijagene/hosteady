import { useQuery } from '@tanstack/react-query'
import { fetchAnnouncements } from '@/api/endpoints/notifications'
import type { NotificationQueryPayload } from '@/api/types/notifications'
import { createInitialNotificationQuery } from '../core/notification-query'
import { toNotificationQueryError } from '../core/notification-errors'

export function useAnnouncements(payload?: NotificationQueryPayload) {
  const queryPayload = createInitialNotificationQuery(payload)

  const query = useQuery({
    queryKey: ['announcements', queryPayload],
    queryFn: () => fetchAnnouncements(queryPayload),
  })

  return {
    query,
    announcements: query.data ?? [],
    error: query.error ? toNotificationQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
