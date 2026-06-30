import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { getApiBaseUrl } from '@/lib/env'
import { buildTenantHeaders, TENANT_HEADERS } from '@/api/tenant-headers'
import type {
  AlphaApiCheck,
  AlphaCheckStatus,
  AlphaFeatureCheck,
  AlphaHealthSnapshot,
  AlphaHealthStatus,
  AlphaRuntimeCheck,
} from '../types'

function check(key: string, label: string, ready: boolean, detail?: string | null, warning = false): AlphaRuntimeCheck {
  return {
    key,
    label,
    status: warning ? 'warning' : ready ? 'ready' : 'unavailable',
    detail: detail ?? (warning ? 'Warning' : ready ? 'Ready' : 'Unavailable'),
  }
}

export function buildAlphaRuntimeChecks(options: {
  authenticated: boolean
  organizationPublicId?: string | null
  workspacePublicId?: string | null
  runtime: HydratedRuntimeBundle | null | undefined
}): AlphaRuntimeCheck[] {
  const { authenticated, organizationPublicId, workspacePublicId, runtime } = options
  const warnings = runtime?.warnings ?? []
  const missingTables = runtime?.personalizationRuntime?.runtime_context?.missing_tables ?? []
  const runtimeWarning = warnings.length > 0 || missingTables.length > 0

  return [
    check('authenticated', 'Authenticated', authenticated),
    check('organization', 'Organization selected', Boolean(organizationPublicId), organizationPublicId ?? undefined),
    check('workspace', 'Workspace selected', Boolean(workspacePublicId), workspacePublicId ?? undefined),
    check('runtime', 'Runtime loaded', Boolean(runtime), runtime?.source),
    check('theme', 'Theme loaded', Boolean(runtime?.themeRuntime), runtime?.themeRuntime?.source),
    check(
      'navigation',
      'Navigation loaded',
      Boolean(runtime?.navigationMenus?.length),
      `${runtime?.navigationMenus?.length ?? 0} menus`,
    ),
    check(
      'personalization',
      'Personalization loaded',
      Boolean(runtime?.personalizationRuntime),
      runtime?.personalizationRuntime?.source,
      runtimeWarning,
    ),
    check(
      'permissions',
      'Permissions loaded',
      Boolean(runtime?.permissions?.length),
      `${runtime?.permissions?.length ?? 0} permissions`,
    ),
  ]
}

export function buildAlphaFeatureChecks(): AlphaFeatureCheck[] {
  return [
    { key: 'admin', label: 'Administration console', available: true },
    { key: 'forms', label: 'Dynamic forms', available: true },
    { key: 'tables', label: 'Dynamic tables', available: true },
    { key: 'dashboards', label: 'Dashboards', available: true },
    { key: 'reports', label: 'Reports', available: true },
    { key: 'documents', label: 'Document manager', available: true },
    { key: 'workflows', label: 'Workflows', available: true },
    { key: 'notifications', label: 'Notifications', available: true },
    { key: 'search', label: 'Global search', available: true },
    { key: 'activity', label: 'Activity & audit', available: true },
  ]
}

export function buildAlphaApiCheck(options: {
  authenticated: boolean
  organizationPublicId?: string | null
  workspacePublicId?: string | null
  runtimeEndpointStatus?: string | null
}): AlphaApiCheck {
  const headers = buildTenantHeaders({
    organizationPublicId: options.organizationPublicId ?? undefined,
    workspacePublicId: options.workspacePublicId ?? undefined,
  })

  return {
    base_url: getApiBaseUrl(),
    token_present: options.authenticated,
    tenant_headers_present: Boolean(
      headers[TENANT_HEADERS.organization] && headers[TENANT_HEADERS.workspace],
    ),
    runtime_endpoint_status: options.runtimeEndpointStatus ?? (options.authenticated ? 'placeholder' : null),
    validated_at: new Date().toISOString(),
  }
}

export function deriveAlphaHealthStatus(checks: AlphaRuntimeCheck[]): AlphaHealthStatus {
  if (checks.some((item) => item.status === 'unavailable')) {
    return 'unavailable'
  }
  if (checks.some((item) => item.status === 'warning')) {
    return 'warning'
  }
  return 'ready'
}

export function buildAlphaHealthSnapshot(options: {
  authenticated: boolean
  organizationPublicId?: string | null
  workspacePublicId?: string | null
  runtime: HydratedRuntimeBundle | null | undefined
  runtimeEndpointStatus?: string | null
}): AlphaHealthSnapshot {
  const runtime = buildAlphaRuntimeChecks(options)
  const features = buildAlphaFeatureChecks()
  const api = buildAlphaApiCheck(options)

  return {
    status: deriveAlphaHealthStatus(runtime),
    runtime,
    features,
    api,
  }
}

export function getAlphaStatusLabel(status: AlphaHealthStatus | AlphaCheckStatus): string {
  switch (status) {
    case 'ready':
      return 'Ready'
    case 'warning':
      return 'Warning'
    case 'unavailable':
      return 'Unavailable'
    default:
      return 'Unknown'
  }
}
