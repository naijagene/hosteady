import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { PlatformOverviewPanel } from '../components/PlatformOverviewPanel'
import { PlatformHealthPanel } from '../components/PlatformHealthPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminOverviewPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Platform Overview" description="HEOS platform status and feature counts.">
      <PlatformOverviewPanel info={admin.platformOverview} />
      <PlatformHealthPanel health={admin.platformHealth} />
    </AdminConsoleLayout>
  )
}
