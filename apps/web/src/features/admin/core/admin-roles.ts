import type { AdminRoleInfo } from '@/api/types/admin'
import { normalizeAdminRoleInfo } from '@/api/types/admin'

export function buildRoleBrowser(roles: string[], permissions: string[]): AdminRoleInfo[] {
  if (roles.length === 0 && permissions.length > 0) {
    const inferred = new Map<string, string[]>()
    for (const permission of permissions) {
      const [prefix] = permission.split('.')
      const key = prefix || 'general'
      inferred.set(key, [...(inferred.get(key) ?? []), permission])
    }
    return Array.from(inferred.entries()).map(([roleKey, rolePermissions]) =>
      normalizeAdminRoleInfo({
        role_key: roleKey,
        label: roleKey.replace(/_/g, ' '),
        description: `Inferred from ${rolePermissions.length} permissions`,
        permission_count: rolePermissions.length,
        member_count: null,
      }),
    )
  }

  return roles.map((role) =>
    normalizeAdminRoleInfo({
      role_key: role,
      label: role.replace(/_/g, ' '),
      description: 'Platform role',
      permission_count: permissions.filter((permission) => permission.startsWith(`${role}.`) || permission.includes(role)).length,
      member_count: null,
    }),
  )
}
