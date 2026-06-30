import {
  fetchApplicationNavigation,
  fetchPersonalizationRuntime,
  fetchThemeRuntime,
  fetchWorkspaceRuntime,
} from '@/api/endpoints/runtime'
import { fetchTenantContext } from '@/api/endpoints/tenant'
import { fetchUnreadNotificationCount } from '@/api/endpoints/notifications'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { ApiRecord } from '@/api/types/api'
import { normalizeNavigationMenus } from '@/features/runtime/core/normalize-navigation'

function resolveNavigationMenus(
  workspaceNavigation: unknown,
  applicationMenus: unknown,
): ReturnType<typeof normalizeNavigationMenus> {
  const normalizedApplicationMenus = normalizeNavigationMenus(applicationMenus)

  if (normalizedApplicationMenus.length > 0) {
    return normalizedApplicationMenus
  }

  return normalizeNavigationMenus(workspaceNavigation)
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

  const menus = resolveNavigationMenus(
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
