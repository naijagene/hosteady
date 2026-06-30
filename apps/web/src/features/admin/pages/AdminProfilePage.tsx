import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { UserProfilePanel } from '../components/UserProfilePanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminProfilePage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="User Profile" description="Current user, roles, permissions, and session information.">
      <UserProfilePanel profile={admin.profile} />
    </AdminConsoleLayout>
  )
}
