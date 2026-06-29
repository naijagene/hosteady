import type { NotificationFilter, NotificationQueryPayload } from '@/api/types/notifications'

export function applyNotificationFilters(
  payload: NotificationQueryPayload,
  filters: NotificationFilter[],
): NotificationQueryPayload {
  return {
    ...payload,
    filters: [...(payload.filters ?? []), ...filters],
  }
}

export function createNotificationStatusFilter(status: string): NotificationFilter {
  return { key: 'status', value: status }
}

export function createNotificationCategoryFilter(category: string): NotificationFilter {
  return { key: 'category', value: category }
}

export function createNotificationPriorityFilter(priority: string): NotificationFilter {
  return { key: 'priority', value: priority }
}
