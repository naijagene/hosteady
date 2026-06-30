import {
  fetchApplicationNavigation,
  fetchNavigationDesignerRuntime,
  type NavigationDesignerRuntimeResponse,
  fetchPersonalizationRuntime,
  fetchThemeRuntime,
  fetchWorkspaceRuntime,
} from '@/api/endpoints/runtime'
import { fetchTenantContext } from '@/api/endpoints/tenant'
import { fetchUnreadNotificationCount } from '@/api/endpoints/notifications'
import { ApiError } from '@/api/errors'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { ApiRecord } from '@/api/types/api'
import {
  hasNavigationItems,
  normalizeNavigationMenus,
} from '@/features/runtime/core/normalize-navigation'

function mergeNavigationSources(...sources: unknown[]): ReturnType<typeof normalizeNavigationMenus> {
  const merged: ReturnType<typeof normalizeNavigationMenus> = []

  for (const source of sources) {
    const menus = normalizeNavigationMenus(source)
    if (menus.length > 0) {
      merged.push(...menus)
    }
  }

  return merged
}

function resolveNavigationMenus(
  workspaceNavigation: unknown,
  applicationMenus: unknown,
  designerRuntime: unknown,
): ReturnType<typeof normalizeNavigationMenus> {
  const application = normalizeNavigationMenus(applicationMenus)
  if (hasNavigationItems(application)) {
    return application
  }

  const designerMenus = normalizeNavigationMenus(
    isRecord(designerRuntime) && Array.isArray(designerRuntime.menus)
      ? designerRuntime.menus
      : designerRuntime,
  )
  if (hasNavigationItems(designerMenus)) {
    return designerMenus
  }

  const workspace = normalizeNavigationMenus(workspaceNavigation)
  if (hasNavigationItems(workspace)) {
    return workspace
  }

  return mergeNavigationSources(application, designerMenus, workspace)
}

function isRecord(value: unknown): value is ApiRecord {
  return value !== null && typeof value === 'object'
}

async function fetchOptionalRuntime<T>(
  request: () => Promise<T>,
  fallback: T,
): Promise<T> {
  try {
    return await request()
  } catch (error) {
    if (error instanceof ApiError && (error.kind === 'unauthorized' || error.kind === 'forbidden')) {
      throw error
    }

    return fallback
  }
}

export async function hydrateRuntimeBundle(): Promise<HydratedRuntimeBundle> {
  const [
    tenantContext,
    workspaceRuntime,
    themeRuntime,
    personalizationRuntime,
    navigationMenus,
    designerRuntime,
    unreadNotificationCount,
  ] = await Promise.all([
    fetchTenantContext(),
    fetchWorkspaceRuntime(),
    fetchOptionalRuntime(() => fetchThemeRuntime(), null),
    fetchOptionalRuntime(() => fetchPersonalizationRuntime(), null),
    fetchOptionalRuntime(() => fetchApplicationNavigation(), []),
    fetchOptionalRuntime(
      () => fetchNavigationDesignerRuntime(),
      {
        menus: [],
        warnings: [],
        source: 'navigation_designer',
      } as NavigationDesignerRuntimeResponse,
    ),
    fetchOptionalRuntime(() => fetchUnreadNotificationCount(), 0),
  ])

  const menus = resolveNavigationMenus(
    workspaceRuntime.navigation,
    navigationMenus,
    designerRuntime,
  )

  const warnings = [
    ...(themeRuntime?.warnings ?? []),
    ...(personalizationRuntime?.warnings ?? []),
    ...(designerRuntime.warnings ?? []),
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
      designerRuntime.source ??
      'heos_runtime',
  }
}
