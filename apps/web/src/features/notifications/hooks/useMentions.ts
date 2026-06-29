import { useQuery } from '@tanstack/react-query'
import { fetchMentions } from '@/api/endpoints/notifications'
import type { NotificationQueryPayload } from '@/api/types/notifications'
import { createInitialNotificationQuery } from '../core/notification-query'
import { toNotificationQueryError } from '../core/notification-errors'

export function useMentions(payload?: NotificationQueryPayload) {
  const queryPayload = createInitialNotificationQuery(payload)

  const query = useQuery({
    queryKey: ['mentions', queryPayload],
    queryFn: () => fetchMentions(queryPayload),
  })

  return {
    query,
    mentions: query.data ?? [],
    error: query.error ? toNotificationQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
