import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { WorkspaceSettingsPanel } from '../components/WorkspaceSettingsPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminWorkspacesPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Workspace Settings" description="Current workspace, applications, navigation, and personalization summary.">
      <WorkspaceSettingsPanel current={admin.workspace.current} workspaces={admin.workspace.workspaces} />
    </AdminConsoleLayout>
  )
}
