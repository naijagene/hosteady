import type { ReportAction } from '@/api/types/reports'

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
  actions: ReportAction[],
  permissions: string[],
): ReportAction[] {
  return actions.filter((action) => hasPermission(permissions, action.permission))
}

export function canRunReport(
  permissions: string[] | Record<string, boolean> | undefined,
): boolean {
  if (Array.isArray(permissions)) {
    return permissions.includes('reports.run') || permissions.length === 0
  }

  if (permissions && typeof permissions === 'object') {
    return permissions.run !== false && permissions.read !== false
  }

  return true
}

export function canExportReport(
  permissions: string[] | Record<string, boolean> | undefined,
): boolean {
  if (Array.isArray(permissions)) {
    return permissions.includes('reports.export') || permissions.length === 0
  }

  if (permissions && typeof permissions === 'object') {
    return permissions.export !== false
  }

  return true
}
