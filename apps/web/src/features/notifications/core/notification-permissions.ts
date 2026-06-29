export function canReadNotifications(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('notifications.read')
}

export function canManageNotifications(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('notifications.manage')
}

export function canDeleteNotifications(permissions: string[]): boolean {
  return permissions.includes('notifications.delete') || permissions.includes('notifications.manage')
}

export function canReadAnnouncements(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('notifications.announcements.read')
}

export function canReadReminders(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('notifications.reminders.read')
}
