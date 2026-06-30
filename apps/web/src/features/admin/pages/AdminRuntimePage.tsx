import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { RuntimeDiagnosticsPanel } from '../components/RuntimeDiagnosticsPanel'
import { ApiDiagnosticsPanel } from '../components/ApiDiagnosticsPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminRuntimePage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="Runtime Diagnostics" description="Runtime and API diagnostics for the current session.">
      <RuntimeDiagnosticsPanel diagnostics={admin.runtimeDiagnostics} />
      <ApiDiagnosticsPanel diagnostics={admin.apiDiagnostics} />
    </AdminConsoleLayout>
  )
}
