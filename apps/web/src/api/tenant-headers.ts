export const TENANT_HEADERS = {
  organization: 'X-HEOS-Organization-Id',
  workspace: 'X-HEOS-Workspace-Id',
  application: 'X-HEOS-Application-Id',
} as const

export interface TenantHeaderValues {
  organizationPublicId?: string | null
  workspacePublicId?: string | null
  applicationPublicId?: string | null
}

export function buildTenantHeaders(
  values: TenantHeaderValues,
): Record<string, string> {
  const headers: Record<string, string> = {}

  if (values.organizationPublicId) {
    headers[TENANT_HEADERS.organization] = values.organizationPublicId
  }

  if (values.workspacePublicId) {
    headers[TENANT_HEADERS.workspace] = values.workspacePublicId
  }

  if (values.applicationPublicId) {
    headers[TENANT_HEADERS.application] = values.applicationPublicId
  }

  return headers
}
