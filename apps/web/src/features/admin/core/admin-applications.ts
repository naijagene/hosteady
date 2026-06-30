import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { AdminApplicationInfo, AdminApplicationRegistry } from '@/api/types/admin'
import { normalizeAdminApplicationInfo } from '@/api/types/admin'
import { asRecord } from '@/api/types/metadata-common'

export function buildApplicationRegistry(
  runtime: HydratedRuntimeBundle | null | undefined,
  remoteApplications: unknown[] = [],
): AdminApplicationRegistry {
  const runtimeApps = (runtime?.workspaceRuntime?.active_applications ?? []).map((app) => normalizeAdminApplicationInfo(app))
  const remote = remoteApplications.map((app) => normalizeAdminApplicationInfo(app))
  const applications = [...runtimeApps, ...remote].reduce<AdminApplicationInfo[]>((acc, app) => {
    if (!acc.some((entry) => entry.public_id === app.public_id)) acc.push(app)
    return acc
  }, [])

  const capabilities = asRecord(runtime?.workspaceRuntime?.capabilities)
  const totals = {
    applications: applications.length,
    navigation: runtime?.navigationMenus?.reduce((total, menu) => total + menu.groups.reduce((groupTotal, group) => groupTotal + group.items.length, 0), 0) ?? 0,
    modules: capabilities.module_count ?? capabilities.modules ?? applications.length,
    ui_pages: capabilities.ui_pages ?? capabilities.pages ?? 0,
    forms: capabilities.forms ?? 0,
    tables: capabilities.tables ?? 0,
    dashboards: capabilities.dashboards ?? 0,
    reports: capabilities.reports ?? 0,
    workflows: capabilities.workflows ?? 0,
    documents: capabilities.documents ?? 0,
  }

  return { applications, totals }
}
