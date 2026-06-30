import type { HydratedRuntimeBundle, NavigationMenuResponse } from '@/api/types/runtime'
import type { AdminWorkspaceInfo } from '@/api/types/admin'
import { normalizeAdminWorkspaceInfo } from '@/api/types/admin'
import type { WorkspaceSummary } from '@/api/types/auth'

function countNavigationItems(menus: NavigationMenuResponse[]): number {
  return menus.reduce((total, menu) => total + menu.groups.reduce((groupTotal, group) => groupTotal + group.items.length, 0), 0)
}

export function buildWorkspaceSettings(
  runtime: HydratedRuntimeBundle | null | undefined,
  workspaces: WorkspaceSummary[] = [],
): { current: AdminWorkspaceInfo; workspaces: AdminWorkspaceInfo[] } {
  const currentWorkspace = runtime?.workspace ?? runtime?.workspaceRuntime?.workspace ?? null
  const applications = runtime?.workspaceRuntime?.active_applications ?? []
  const personalization = runtime?.personalizationRuntime

  const current = normalizeAdminWorkspaceInfo(currentWorkspace ?? {}, {
    theme_source: runtime?.themeRuntime?.source ?? null,
    application_count: applications.length,
    navigation_count: countNavigationItems(runtime?.navigationMenus ?? []),
    personalization_summary: {
      favorites: personalization?.favorites?.length ?? 0,
      recent_items: personalization?.recent_items?.length ?? 0,
      shortcuts: personalization?.shortcuts?.length ?? 0,
      quick_actions: personalization?.quick_actions?.length ?? 0,
    },
  })

  const workspaceList = (workspaces.length > 0 ? workspaces : currentWorkspace ? [currentWorkspace] : []).map((workspace) =>
    normalizeAdminWorkspaceInfo(workspace, workspace.public_id === current.public_id ? {
      theme_source: current.theme_source,
      application_count: current.application_count,
      navigation_count: current.navigation_count,
      personalization_summary: current.personalization_summary,
    } : {}),
  )

  return { current, workspaces: workspaceList }
}

export function getWorkspaceDisplayFields(info: AdminWorkspaceInfo): Array<{ label: string; value: string }> {
  return [
    { label: 'Workspace', value: info.name ?? '—' },
    { label: 'Slug', value: info.slug ?? '—' },
    { label: 'Status', value: info.status ?? '—' },
    { label: 'Theme', value: info.theme_source ?? '—' },
    { label: 'Applications', value: String(info.application_count ?? 0) },
    { label: 'Navigation items', value: String(info.navigation_count ?? 0) },
  ]
}
