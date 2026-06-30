import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { OrganizationSettingsPanel } from '../components/OrganizationSettingsPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminOrganizationPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Organization Settings" description="Organization metadata from hydrated runtime.">
      <OrganizationSettingsPanel organization={admin.organization} />
    </AdminConsoleLayout>
  )
}
