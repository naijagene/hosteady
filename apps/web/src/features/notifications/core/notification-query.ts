import type { Notification, NotificationQueryPayload } from '@/api/types/notifications'

export function createInitialNotificationQuery(
  overrides?: Partial<NotificationQueryPayload>,
): NotificationQueryPayload {
  return {
    page: 1,
    per_page: 25,
    search: '',
    sort_key: 'created_at',
    sort_direction: 'desc',
    metadata: { source: 'web' },
    ...overrides,
  }
}

export function mergeNotificationQueryPayload(
  current: NotificationQueryPayload,
  patch: Partial<NotificationQueryPayload>,
): NotificationQueryPayload {
  return { ...current, ...patch }
}

export function paginateNotifications(
  items: Notification[],
  page: number,
  perPage: number,
): { items: Notification[]; total: number; has_more: boolean } {
  const start = (page - 1) * perPage
  const slice = items.slice(start, start + perPage)
  return {
    items: slice,
    total: items.length,
    has_more: start + perPage < items.length,
  }
}

export function queryNotificationsLocally(
  items: Notification[],
  payload: NotificationQueryPayload,
): { items: Notification[]; total: number; has_more: boolean } {
  const search = payload.search?.trim().toLowerCase() ?? ''
  let filtered = items

  if (payload.unread_only) {
    filtered = filtered.filter((item) => !item.read_at)
  }

  if (payload.status) {
    filtered = filtered.filter((item) => item.status === payload.status)
  }

  if (payload.category && payload.category !== 'all') {
    filtered = filtered.filter((item) => item.category === payload.category)
  }

  if (search) {
    filtered = filtered.filter((item) =>
      [item.title, item.body, item.category, item.template_key]
        .filter((value): value is string => typeof value === 'string')
        .some((value) => value.toLowerCase().includes(search)),
    )
  }

  filtered = [...filtered].sort((left, right) => {
    const key = payload.sort_key ?? 'created_at'
    const leftValue = key === 'title' ? left.title : (left.created_at ?? '')
    const rightValue = key === 'title' ? right.title : (right.created_at ?? '')
    const compare = leftValue.localeCompare(rightValue)
    return payload.sort_direction === 'asc' ? compare : -compare
  })

  return paginateNotifications(filtered, payload.page ?? 1, payload.per_page ?? 25)
}

export function buildNotificationTabCounts(items: Notification[]) {
  return {
    all: items.length,
    unread: items.filter((item) => !item.read_at).length,
    announcements: items.filter((item) => item.category === 'announcement').length,
    mentions: items.filter((item) => item.category === 'mention').length,
    reminders: items.filter((item) => item.category === 'reminder').length,
    workflow: items.filter((item) => item.category === 'workflow').length,
    documents: items.filter((item) => item.category === 'document').length,
    system: items.filter((item) => item.category === 'system').length,
  }
}

export function resolveTabCategory(tab: string): string | undefined {
  switch (tab) {
    case 'unread':
      return undefined
    case 'announcements':
      return 'announcement'
    case 'mentions':
      return 'mention'
    case 'reminders':
      return 'reminder'
    case 'workflow':
      return 'workflow'
    case 'documents':
      return 'document'
    case 'system':
      return 'system'
    default:
      return undefined
  }
}
