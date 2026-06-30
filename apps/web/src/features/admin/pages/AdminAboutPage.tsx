import { AdminConsoleLayout } from '../components/AdminConsoleLayout'
import { AboutHeosPanel } from '../components/AboutHeosPanel'
import { useAdminConsole } from '../hooks/useAdminConsole'

export function AdminAboutPage() {
  const admin = useAdminConsole()
  return (
    <AdminConsoleLayout title="About HEOS" description="Platform information and technology stack.">
      <AboutHeosPanel about={admin.about} />
    </AdminConsoleLayout>
  )
}
