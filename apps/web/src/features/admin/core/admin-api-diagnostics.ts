import type { AdminApiDiagnostic } from '@/api/types/admin'
import { getApiBaseUrl } from '@/lib/env'
import { buildTenantHeaders } from '@/api/tenant-headers'

export function buildApiDiagnostics(options: {
  authenticated: boolean
  organizationPublicId?: string | null
  workspacePublicId?: string | null
  connectedEndpoints?: string[]
}): AdminApiDiagnostic {
  return {
    base_url: getApiBaseUrl(),
    authenticated: options.authenticated,
    tenant_headers: buildTenantHeaders({
      organizationPublicId: options.organizationPublicId ?? undefined,
      workspacePublicId: options.workspacePublicId ?? undefined,
    }),
    latency_ms: null,
    connected_endpoints: options.connectedEndpoints ?? [
      'tenant/context',
      'tenant/workspace/runtime',
      'tenant/themes/runtime',
      'tenant/personalization/runtime',
      'tenant/application-runtime/navigation',
      'tenant/notifications',
      'tenant/search',
      'tenant/audit/events',
    ],
  }
}
