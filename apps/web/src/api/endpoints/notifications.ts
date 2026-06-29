import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import type { NotificationSummaryResponse } from '../types/runtime'

export async function fetchUnreadNotifications(
  limit = 50,
): Promise<NotificationSummaryResponse[]> {
  const response = await apiClient.get<
    NotificationSummaryResponse[] | { data: NotificationSummaryResponse[] }
  >('tenant/notifications', { params: { limit } })

  return unwrapData(response.data)
}

export async function fetchUnreadNotificationCount(): Promise<number> {
  const notifications = await fetchUnreadNotifications()
  return notifications.filter((notification) => notification.read_at === null)
    .length
}
