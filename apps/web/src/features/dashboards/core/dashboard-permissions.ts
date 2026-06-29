import type { DashboardWidget } from '@/api/types/dashboards'

export function hasPermission(
  permissions: string[],
  required?: string | null,
): boolean {
  if (!required) {
    return true
  }

  return permissions.includes(required)
}

export function filterWidgetsByPermission(
  widgets: DashboardWidget[],
  permissions: string[],
): DashboardWidget[] {
  return widgets.filter((widget) => hasPermission(permissions, widget.permission))
}

export function filterActionsByPermission<T extends { permission?: string | null }>(
  actions: T[],
  permissions: string[],
): T[] {
  return actions.filter((action) => hasPermission(permissions, action.permission))
}

export function canRenderDashboard(
  permissions: string[] | Record<string, boolean> | undefined,
): boolean {
  if (Array.isArray(permissions)) {
    return permissions.includes('dashboards.render') || permissions.length === 0
  }

  if (permissions && typeof permissions === 'object') {
    return permissions.render !== false && permissions.read !== false
  }

  return true
}
