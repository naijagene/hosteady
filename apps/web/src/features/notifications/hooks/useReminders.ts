import { useQuery } from '@tanstack/react-query'
import { fetchReminders } from '@/api/endpoints/notifications'
import type { NotificationQueryPayload } from '@/api/types/notifications'
import { createInitialNotificationQuery } from '../core/notification-query'
import { toNotificationQueryError } from '../core/notification-errors'

export function useReminders(payload?: NotificationQueryPayload) {
  const queryPayload = createInitialNotificationQuery(payload)

  const query = useQuery({
    queryKey: ['reminders', queryPayload],
    queryFn: () => fetchReminders(queryPayload),
  })

  return {
    query,
    reminders: query.data ?? [],
    error: query.error ? toNotificationQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
