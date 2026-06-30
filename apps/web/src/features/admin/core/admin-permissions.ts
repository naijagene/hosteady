import type { AdminPermissionInfo } from '@/api/types/admin'
import { normalizeAdminPermissionInfo } from '@/api/types/admin'

export function buildPermissionBrowser(permissions: string[]): AdminPermissionInfo[] {
  return permissions.map(normalizeAdminPermissionInfo).sort((left, right) => left.permission.localeCompare(right.permission))
}

export function groupPermissionsByCategory(permissions: AdminPermissionInfo[]): Record<string, AdminPermissionInfo[]> {
  return permissions.reduce<Record<string, AdminPermissionInfo[]>>((groups, permission) => {
    groups[permission.category] = groups[permission.category] ?? []
    groups[permission.category].push(permission)
    return groups
  }, {})
}

export function filterPermissions(permissions: AdminPermissionInfo[], search: string): AdminPermissionInfo[] {
  const normalized = search.trim().toLowerCase()
  if (!normalized) return permissions
  return permissions.filter((permission) =>
    [permission.permission, permission.category, permission.label ?? ''].join(' ').toLowerCase().includes(normalized),
  )
}

export async function copyPermissionToClipboard(permission: string): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(permission)
    return true
  } catch {
    return false
  }
}
