import type { AdminRuntimeDiagnostic } from '@/api/types/admin'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { asString } from '@/api/types/metadata-common'

function diagnostic(key: string, label: string, loaded: boolean, detail?: string | null): AdminRuntimeDiagnostic {
  return {
    key,
    label,
    status: loaded ? 'loaded' : 'unavailable',
    detail: detail ?? (loaded ? 'Loaded' : 'Unavailable'),
  }
}

export function buildRuntimeDiagnostics(runtime: HydratedRuntimeBundle | null | undefined): AdminRuntimeDiagnostic[] {
  const activeApplication = runtime?.application ?? runtime?.workspaceRuntime?.active_application
  const installedApplications = runtime?.workspaceRuntime?.active_applications ?? []
  const activeApplicationLoaded = Boolean(activeApplication)
  const activeApplicationName = asString(
    runtime?.application?.name ??
      (typeof activeApplication === 'object' && activeApplication !== null
        ? (activeApplication as { name?: unknown }).name
        : undefined),
  )
  const activeApplicationDetail =
    activeApplicationName ||
    (installedApplications.length > 0
      ? `${installedApplications.length} installed (none selected)`
      : undefined)

  return [
    diagnostic('theme', 'Theme loaded', Boolean(runtime?.themeRuntime), runtime?.themeRuntime?.source),
    diagnostic('navigation', 'Navigation loaded', Boolean(runtime?.navigationMenus?.length), `${runtime?.navigationMenus?.length ?? 0} menus`),
    diagnostic('personalization', 'Personalization loaded', Boolean(runtime?.personalizationRuntime), runtime?.personalizationRuntime?.source),
    diagnostic('runtime', 'Runtime loaded', Boolean(runtime), runtime?.source),
    diagnostic('workspace', 'Workspace loaded', Boolean(runtime?.workspace ?? runtime?.workspaceRuntime?.workspace), runtime?.workspace?.name ?? undefined),
    diagnostic(
      'application',
      'Active application',
      activeApplicationLoaded,
      activeApplicationDetail,
    ),
    diagnostic('search', 'Search loaded', true, 'Global search feature available'),
    diagnostic('activity', 'Activity loaded', true, 'Activity center feature available'),
    diagnostic('notifications', 'Notifications loaded', runtime?.unreadNotificationCount !== undefined, `${runtime?.unreadNotificationCount ?? 0} unread`),
  ]
}
