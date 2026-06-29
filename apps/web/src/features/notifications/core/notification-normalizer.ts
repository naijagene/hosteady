import type { Notification } from '@/api/types/notifications'

export function getNotificationDisplayTitle(notification: Notification): string {
  return notification.title || notification.public_id
}

export function formatNotificationDate(value?: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

export function isNotificationUnread(notification: Notification): boolean {
  return !notification.read_at
}
