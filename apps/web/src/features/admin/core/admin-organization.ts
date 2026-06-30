import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { AdminOrganizationInfo } from '@/api/types/admin'
import { normalizeAdminOrganizationInfo } from '@/api/types/admin'

export function buildOrganizationSettings(runtime: HydratedRuntimeBundle | null | undefined): AdminOrganizationInfo {
  const organization = runtime?.organization ?? runtime?.workspaceRuntime?.organization ?? runtime?.tenantContext?.organization
  const metadata = runtime?.workspaceRuntime?.settings_metadata ?? {}
  return normalizeAdminOrganizationInfo({ ...(organization ?? {}), metadata })
}

export function getOrganizationDisplayFields(info: AdminOrganizationInfo): Array<{ label: string; value: string }> {
  return [
    { label: 'Organization', value: info.name ?? '—' },
    { label: 'Description', value: info.description ?? '—' },
    { label: 'Logo', value: info.logo ?? 'Not configured' },
    { label: 'Brand', value: info.brand ?? '—' },
    { label: 'Time zone', value: info.timezone ?? '—' },
    { label: 'Locale', value: info.locale ?? '—' },
    { label: 'Currency', value: info.currency ?? '—' },
    { label: 'Contact info', value: info.contact_info ?? '—' },
    { label: 'Created date', value: info.created_at ?? '—' },
  ]
}
