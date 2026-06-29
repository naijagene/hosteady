import { useMemo, useState } from 'react'
import type { NotificationBindingContext } from '@/api/types/notifications'
import { buildNotificationTabCounts, resolveTabCategory } from '../core/notification-query'
import { useNotifications } from './useNotifications'
import type { NotificationCenterTab } from '../types'

export function useNotificationCenter(binding?: NotificationBindingContext) {
  const initialTab: NotificationCenterTab =
    binding?.mode === 'announcements'
      ? 'announcements'
      : binding?.mode === 'mentions'
        ? 'mentions'
        : binding?.mode === 'reminders'
          ? 'reminders'
          : binding?.mode === 'workflow'
            ? 'workflow'
            : binding?.mode === 'documents'
              ? 'documents'
              : 'all'

  const [activeTab, setActiveTab] = useState<NotificationCenterTab>(initialTab)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [priorityFilter, setPriorityFilter] = useState('')

  const notificationsQuery = useNotifications(binding)

  const tabCategory = resolveTabCategory(activeTab)
  const unreadOnly = activeTab === 'unread'

  const filteredItems = useMemo(() => {
    let items = notificationsQuery.notifications

    if (unreadOnly) {
      items = items.filter((item) => !item.read_at)
    } else if (tabCategory) {
      items = items.filter((item) => item.category === tabCategory)
    }

    if (statusFilter) {
      items = items.filter((item) => item.status === statusFilter)
    }

    if (priorityFilter) {
      items = items.filter((item) => item.priority === priorityFilter)
    }

    if (search.trim()) {
      const normalized = search.trim().toLowerCase()
      items = items.filter((item) =>
        [item.title, item.body].some((value) => value.toLowerCase().includes(normalized)),
      )
    }

    return items
  }, [notificationsQuery.notifications, priorityFilter, search, statusFilter, tabCategory, unreadOnly])

  const counts = useMemo(
    () => buildNotificationTabCounts(notificationsQuery.notifications),
    [notificationsQuery.notifications],
  )

  return {
    activeTab,
    setActiveTab,
    search,
    setSearch,
    statusFilter,
    setStatusFilter,
    priorityFilter,
    setPriorityFilter,
    items: filteredItems,
    counts,
    isLoading: notificationsQuery.query.isLoading,
    error: notificationsQuery.error,
    refresh: notificationsQuery.refresh,
    updateQueryPayload: notificationsQuery.updateQueryPayload,
  }
}
