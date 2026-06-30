export function canReadActivity(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('activity.read') || permissions.includes('audit.read')
}

export function canReadAudit(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('audit.read') || permissions.includes('security.audit.read')
}

export function canReadHistory(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('history.read') || permissions.includes('audit.read')
}

export function canViewActivityEntry(permissions: string[], required?: string | null): boolean {
  if (!required) return true
  if (permissions.length === 0) return true
  return permissions.includes(required)
}

export function filterActivityByPermission<T extends { permission?: string | null }>(items: T[], permissions: string[]): T[] {
  return items.filter((item) => canViewActivityEntry(permissions, item.permission))
}
