import {
  fetchApplicationNavigation,
  fetchPersonalizationRuntime,
  fetchThemeRuntime,
  fetchWorkspaceRuntime,
} from '@/api/endpoints/runtime'
import { fetchTenantContext } from '@/api/endpoints/tenant'
import { fetchUnreadNotificationCount } from '@/api/endpoints/notifications'
import type {
  HydratedRuntimeBundle,
  NavigationMenuResponse,
} from '@/api/types/runtime'
import type { ApiRecord } from '@/api/types/api'

function normalizeNavigationMenus(
  workspaceNavigation: ApiRecord | ApiRecord[],
  applicationMenus: NavigationMenuResponse[],
): NavigationMenuResponse[] {
  if (applicationMenus.length > 0) {
    return applicationMenus
  }

  if (Array.isArray(workspaceNavigation)) {
    return workspaceNavigation as unknown as NavigationMenuResponse[]
  }

  if (
    workspaceNavigation &&
    typeof workspaceNavigation === 'object' &&
    Array.isArray((workspaceNavigation as ApiRecord).menus)
  ) {
    return (workspaceNavigation as ApiRecord).menus as NavigationMenuResponse[]
  }

  if (
    workspaceNavigation &&
    typeof workspaceNavigation === 'object' &&
    Array.isArray((workspaceNavigation as ApiRecord).groups)
  ) {
    return [
      {
        menu_key: 'main',
        label: 'Main',
        groups: (workspaceNavigation as ApiRecord)
          .groups as NavigationMenuResponse['groups'],
        metadata: (workspaceNavigation as ApiRecord).metadata as ApiRecord,
      },
    ]
  }

  return []
}

export async function hydrateRuntimeBundle(): Promise<HydratedRuntimeBundle> {
  const [
    tenantContext,
    workspaceRuntime,
    themeRuntime,
    personalizationRuntime,
    navigationMenus,
    unreadNotificationCount,
  ] = await Promise.all([
    fetchTenantContext(),
    fetchWorkspaceRuntime(),
    fetchThemeRuntime().catch(() => null),
    fetchPersonalizationRuntime().catch(() => null),
    fetchApplicationNavigation().catch(() => []),
    fetchUnreadNotificationCount().catch(() => 0),
  ])

  const menus = normalizeNavigationMenus(
    workspaceRuntime.navigation,
    navigationMenus,
  )

  const warnings = [
    ...(themeRuntime?.warnings ?? []),
    ...(personalizationRuntime?.warnings ?? []),
  ]

  const activeApplication = workspaceRuntime.active_application

  return {
    tenantContext,
    workspaceRuntime,
    themeRuntime,
    personalizationRuntime,
    navigationMenus: menus,
    permissions: tenantContext.permissions,
    roles: [],
    user: tenantContext.user,
    organization: tenantContext.organization,
    workspace: tenantContext.workspace,
    membership: tenantContext.membership,
    application:
      activeApplication && typeof activeApplication === 'object'
        ? {
            public_id: String(
              (activeApplication as ApiRecord).public_id ??
                (activeApplication as ApiRecord).application_public_id ??
                '',
            ),
            name:
              typeof (activeApplication as ApiRecord).name === 'string'
                ? ((activeApplication as ApiRecord).name as string)
                : undefined,
            key:
              typeof (activeApplication as ApiRecord).key === 'string'
                ? ((activeApplication as ApiRecord).key as string)
                : undefined,
          }
        : null,
    unreadNotificationCount,
    warnings,
    source:
      personalizationRuntime?.source ??
      themeRuntime?.source ??
      'heos_runtime',
  }
}
