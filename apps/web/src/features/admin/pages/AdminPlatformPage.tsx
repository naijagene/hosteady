import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { PlatformHealthPanel } from '../components/PlatformHealthPanel'
import { FeatureFlagsPanel } from '../components/FeatureFlagsPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminPlatformPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Platform Health" description="Platform health and feature flags.">
      <PlatformHealthPanel health={admin.platformHealth} />
      <FeatureFlagsPanel flags={admin.featureFlags} />
    </AdminConsoleLayout>
  )
}
