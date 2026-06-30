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
      Array.isArray(runtime?.permissions),
      `${runtime?.permissions?.length ?? 0} permissions`,
    ),
  ]
}

export function buildAlphaFeatureChecks(
  runtime: HydratedRuntimeBundle | null | undefined,
  permissions: string[],
): AlphaFeatureCheck[] {
  const hasRuntime = Boolean(runtime)
  const allow = (required?: string | string[]) => {
    if (!hasRuntime) return false
    if (permissions.length === 0) return true
    const keys = Array.isArray(required) ? required : required ? [required] : []
    if (keys.length === 0) return true
    return keys.some((key) => permissions.includes(key))
  }

  return [
    { key: 'admin', label: 'Administration console', available: allow(['platform.read', 'settings.read']) },
    { key: 'forms', label: 'Dynamic forms', available: allow(['forms.read', 'ui.render']) },
    { key: 'tables', label: 'Dynamic tables', available: allow(['tables.read', 'ui.render']) },
    { key: 'dashboards', label: 'Dashboards', available: allow(['dashboards.read', 'ui.render']) },
    { key: 'reports', label: 'Reports', available: allow(['reports.read', 'ui.render']) },
    { key: 'documents', label: 'Document manager', available: allow(['documents.read']) },
    { key: 'workflows', label: 'Workflows', available: allow(['workflow.read', 'task.read']) },
    { key: 'notifications', label: 'Notifications', available: allow(['notifications.read']) },
    { key: 'search', label: 'Global search', available: allow(['search.read']) },
    { key: 'activity', label: 'Activity & audit', available: allow(['audit.read']) },
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
  const runtimeChecks = buildAlphaRuntimeChecks(options)
  const permissions = options.runtime?.permissions ?? []
  const features = buildAlphaFeatureChecks(options.runtime, permissions)
  const api = buildAlphaApiCheck(options)

  const featureUnavailable = features.some((feature) => !feature.available)
  const status = deriveAlphaHealthStatus([
    ...runtimeChecks,
    ...(featureUnavailable
      ? [{ key: 'features', label: 'Feature availability', status: 'warning' as const, detail: 'Some features unavailable for current permissions' }]
      : []),
  ])

  return {
    status,
    runtime: runtimeChecks,
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
