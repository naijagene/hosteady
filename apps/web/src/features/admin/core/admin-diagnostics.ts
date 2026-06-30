import type { AdminRuntimeDiagnostic } from '@/api/types/admin'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

function diagnostic(key: string, label: string, loaded: boolean, detail?: string | null): AdminRuntimeDiagnostic {
  return {
    key,
    label,
    status: loaded ? 'loaded' : 'unavailable',
    detail: detail ?? (loaded ? 'Loaded' : 'Unavailable'),
  }
}

export function buildRuntimeDiagnostics(runtime: HydratedRuntimeBundle | null | undefined): AdminRuntimeDiagnostic[] {
  return [
    diagnostic('theme', 'Theme loaded', Boolean(runtime?.themeRuntime), runtime?.themeRuntime?.source),
    diagnostic('navigation', 'Navigation loaded', Boolean(runtime?.navigationMenus?.length), `${runtime?.navigationMenus?.length ?? 0} menus`),
    diagnostic('personalization', 'Personalization loaded', Boolean(runtime?.personalizationRuntime), runtime?.personalizationRuntime?.source),
    diagnostic('runtime', 'Runtime loaded', Boolean(runtime), runtime?.source),
    diagnostic('workspace', 'Workspace loaded', Boolean(runtime?.workspace ?? runtime?.workspaceRuntime?.workspace), runtime?.workspace?.name ?? undefined),
    diagnostic('application', 'Application loaded', Boolean(runtime?.application ?? runtime?.workspaceRuntime?.active_application), runtime?.application?.name ?? undefined),
    diagnostic('search', 'Search loaded', true, 'Global search feature available'),
    diagnostic('activity', 'Activity loaded', true, 'Activity center feature available'),
    diagnostic('notifications', 'Notifications loaded', runtime?.unreadNotificationCount !== undefined, `${runtime?.unreadNotificationCount ?? 0} unread`),
  ]
}
