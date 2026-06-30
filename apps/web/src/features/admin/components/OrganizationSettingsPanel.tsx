import type { AdminOrganizationInfo } from '@/api/types/admin'
import { getOrganizationDisplayFields } from '../core/admin-organization'
import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

export function OrganizationSettingsPanel({ organization }: { organization: AdminOrganizationInfo }) {
  return (
    <AdminSection title="Organization Settings" description="Read-only organization metadata from hydrated runtime.">
      <AdminDefinitionList items={getOrganizationDisplayFields(organization)} />
    </AdminSection>
  )
}
