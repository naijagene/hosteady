import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { ApplicationRegistryPanel } from '../components/ApplicationRegistryPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminApplicationsPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Application Registry" description="Installed applications and platform resource totals.">
      <ApplicationRegistryPanel registry={admin.applications} />
    </AdminConsoleLayout>
  )
}
