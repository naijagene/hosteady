import type { AdminUserProfile } from '@/api/types/admin'
import { getUserProfileFields } from '../core/admin-profile'
import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

export function UserProfilePanel({ profile }: { profile: AdminUserProfile }) {
  return (
    <AdminSection title="User Profile">
      <AdminDefinitionList items={getUserProfileFields(profile)} />
    </AdminSection>
  )
}
