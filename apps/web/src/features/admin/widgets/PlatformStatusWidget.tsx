import { useAdminConsole } from '../hooks/useAdminConsole'
import { AdminStatusBadge } from '../components/AdminStatusBadge'
import { getHealthLabel } from '../core/admin-health'

export function PlatformStatusWidget() {
  const admin = useAdminConsole()
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="platform-status-widget">
      <h2 className="text-sm font-semibold text-foreground">Platform Status</h2>
      <div className="mt-3 flex items-center gap-2">
        <AdminStatusBadge status={admin.platformHealth.status} />
        <span className="text-sm text-foreground">{getHealthLabel(admin.platformHealth.status)}</span>
      </div>
      <p className="mt-2 text-xs text-muted-foreground">{admin.platformOverview.runtime_status}</p>
    </section>
  )
}
