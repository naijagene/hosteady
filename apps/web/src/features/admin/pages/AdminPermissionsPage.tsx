import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { PermissionBrowserPanel } from '../components/PermissionBrowserPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminPermissionsPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Permission Browser" description="Search and copy hydrated runtime permissions.">
      <PermissionBrowserPanel permissions={admin.permissions} />
    </AdminConsoleLayout>
  )
}
