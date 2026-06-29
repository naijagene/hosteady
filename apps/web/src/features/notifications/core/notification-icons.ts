import type { NotificationCategory } from '@/api/types/notifications'

export function resolveNotificationIcon(category?: NotificationCategory | string): string {
  switch (category) {
    case 'announcement':
      return 'announcement'
    case 'mention':
      return 'mention'
    case 'reminder':
      return 'reminder'
    case 'workflow':
      return 'workflow'
    case 'document':
      return 'document'
    case 'report':
      return 'report'
    case 'dashboard':
      return 'dashboard'
    default:
      return 'notification'
  }
}

export function getNotificationIconLabel(category?: NotificationCategory | string): string {
  return resolveNotificationIcon(category)
}
