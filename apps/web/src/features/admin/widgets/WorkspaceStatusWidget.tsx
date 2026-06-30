import { useAdminConsole } from '../hooks/useAdminConsole'

export function WorkspaceStatusWidget() {
  const admin = useAdminConsole()
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="workspace-status-widget">
      <h2 className="text-sm font-semibold text-foreground">Workspace Status</h2>
      <p className="mt-2 text-sm font-medium text-foreground">{admin.workspace.current.name ?? 'Unknown workspace'}</p>
      <p className="text-xs text-muted-foreground">{admin.workspace.current.status ?? 'Unknown status'}</p>
      <p className="mt-2 text-xs text-muted-foreground">{admin.workspace.current.application_count ?? 0} applications · {admin.workspace.current.navigation_count ?? 0} navigation items</p>
    </section>
  )
}
