import type { TableAction } from '@/api/types/tables'

export function hasPermission(
  permissions: string[],
  required?: string | null,
): boolean {
  if (!required) {
    return true
  }

  return permissions.includes(required)
}

export function filterActionsByPermission(
  actions: TableAction[],
  permissions: string[],
): TableAction[] {
  return actions.filter((action) => hasPermission(permissions, action.permission))
}

export function canCreate(options: {
  permissions: string[]
  createEnabled?: boolean
  createPermission?: string | null
}): boolean {
  if (options.createEnabled === false) {
    return false
  }

  return hasPermission(options.permissions, options.createPermission)
}

export function canEdit(options: {
  permissions: string[]
  editEnabled?: boolean
  editPermission?: string | null
}): boolean {
  if (options.editEnabled === false) {
    return false
  }

  return hasPermission(options.permissions, options.editPermission)
}

export function canDelete(options: {
  permissions: string[]
  deleteEnabled?: boolean
  deletePermission?: string | null
}): boolean {
  if (options.deleteEnabled === false) {
    return false
  }

  return hasPermission(options.permissions, options.deletePermission)
}
