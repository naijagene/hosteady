import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { RoleBrowserPanel } from '../components/RoleBrowserPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminRolesPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Role Browser" description="Browse platform roles and permission counts.">
      <RoleBrowserPanel roles={admin.roles} />
    </AdminConsoleLayout>
  )
}
