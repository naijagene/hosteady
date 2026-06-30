import type { ActivityEntry } from '@/api/types/activity'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

export function buildRuntimeActivityPlaceholders(runtime: HydratedRuntimeBundle | null | undefined): ActivityEntry[] {
  if (!runtime) return []

  const now = new Date().toISOString()
  const items: ActivityEntry[] = []

  if ((runtime.unreadNotificationCount ?? 0) > 0) {
    items.push({
      public_id: 'runtime-notifications',
      occurred_at: now,
      action: 'notifications.unread',
      summary: `${runtime.unreadNotificationCount} unread notifications`,
      severity: 'info',
      category: 'notification',
      entity: { type: 'notification', label: 'Notifications', route: '/notifications' },
      source: 'runtime',
      permission: 'notifications.read',
    })
  }

  const recentItems = runtime.personalizationRuntime?.recent_items ?? []
  for (const item of recentItems.slice(0, 3)) {
    const label = typeof item.label === 'string' ? item.label : typeof item.title === 'string' ? item.title : 'Recent item'
    items.push({
      public_id: `runtime-recent-${label}`,
      occurred_at: now,
      action: 'recent.viewed',
      summary: `Recently viewed ${label}`,
      severity: 'info',
      category: 'personalization',
      entity: { type: 'page', label, route: typeof item.route === 'string' ? item.route : null },
      source: 'runtime',
    })
  }

  return items
}
