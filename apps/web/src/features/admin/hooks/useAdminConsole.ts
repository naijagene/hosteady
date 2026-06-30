import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchUiPages } from '@/api/endpoints/ui'
import { fetchWorkspaceRuntimeHealth, safeFetchTenantApplications } from '@/api/endpoints/admin'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { useAuthStore } from '@/stores/auth-store'
import { buildPlatformOverview } from '../core/admin-platform'
import { buildOrganizationSettings } from '../core/admin-organization'
import { buildWorkspaceSettings } from '../core/admin-workspace'
import { buildUserProfile } from '../core/admin-profile'
import { buildRoleBrowser } from '../core/admin-roles'
import { buildPermissionBrowser } from '../core/admin-permissions'
import { buildApplicationRegistry } from '../core/admin-applications'
import { buildRuntimeDiagnostics } from '../core/admin-diagnostics'
import { buildApiDiagnostics } from '../core/admin-api-diagnostics'
import { buildFeatureFlags } from '../core/admin-feature-flags'
import { buildAboutHeos } from '../core/admin-about'
import { deriveRuntimeHealth, mergePlatformHealth } from '../core/admin-health'

export function useAdminConsole() {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(() => runtime?.permissions ?? [], [runtime?.permissions])
  const workspaces = useAuthStore((state) => state.workspaces)
  const expiresAt = useAuthStore((state) => state.expiresAt)
  const accessToken = useAuthStore((state) => state.accessToken)
  const organizationPublicId = useAuthStore((state) => state.organizationPublicId)
  const workspacePublicId = useAuthStore((state) => state.workspacePublicId)

  const pagesQuery = useQuery({ queryKey: ['admin-ui-pages'], queryFn: fetchUiPages, enabled: Boolean(runtime) })
  const healthQuery = useQuery({ queryKey: ['admin-runtime-health'], queryFn: fetchWorkspaceRuntimeHealth, enabled: Boolean(runtime) })
  const applicationsQuery = useQuery({ queryKey: ['admin-applications'], queryFn: safeFetchTenantApplications, enabled: Boolean(runtime) })

  const featureCounts = useMemo(
    () => ({
      applications: runtime?.workspaceRuntime?.active_applications?.length ?? 0,
      navigation: runtime?.navigationMenus?.reduce((total, menu) => total + menu.groups.reduce((groupTotal, group) => groupTotal + group.items.length, 0), 0) ?? 0,
      ui_pages: pagesQuery.data?.length ?? 0,
      permissions: permissions.length,
      roles: runtime?.roles?.length ?? 0,
      notifications: runtime?.unreadNotificationCount ?? 0,
    }),
    [pagesQuery.data?.length, permissions.length, runtime],
  )

  const platformOverview = useMemo(() => buildPlatformOverview(runtime, featureCounts), [featureCounts, runtime])
  const organization = useMemo(() => buildOrganizationSettings(runtime), [runtime])
  const workspace = useMemo(() => buildWorkspaceSettings(runtime, workspaces), [runtime, workspaces])
  const profile = useMemo(() => buildUserProfile(runtime, expiresAt), [expiresAt, runtime])
  const roles = useMemo(() => buildRoleBrowser(runtime?.roles ?? [], permissions), [permissions, runtime?.roles])
  const permissionBrowser = useMemo(() => buildPermissionBrowser(permissions), [permissions])
  const applications = useMemo(
    () => buildApplicationRegistry(runtime, applicationsQuery.data ?? []),
    [applicationsQuery.data, runtime],
  )
  const runtimeDiagnostics = useMemo(() => buildRuntimeDiagnostics(runtime), [runtime])
  const apiDiagnostics = useMemo(
    () =>
      buildApiDiagnostics({
        authenticated: Boolean(accessToken),
        organizationPublicId,
        workspacePublicId,
      }),
    [accessToken, organizationPublicId, workspacePublicId],
  )
  const featureFlags = useMemo(() => buildFeatureFlags(runtime), [runtime])
  const about = useMemo(() => buildAboutHeos(), [])
  const platformHealth = useMemo(
    () => mergePlatformHealth(healthQuery.data, deriveRuntimeHealth(runtime)),
    [healthQuery.data, runtime],
  )

  return {
    runtime,
    permissions,
    platformOverview,
    organization,
    workspace,
    profile,
    roles,
    permissionBrowser,
    applications,
    runtimeDiagnostics,
    apiDiagnostics,
    featureFlags,
    about,
    platformHealth,
    isLoading: pagesQuery.isLoading || healthQuery.isLoading || applicationsQuery.isLoading,
  }
}
