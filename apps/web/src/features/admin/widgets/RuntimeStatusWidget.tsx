import { useAdminConsole } from '../hooks/useAdminConsole'

export function RuntimeStatusWidget() {
  const admin = useAdminConsole()
  const loaded = admin.runtimeDiagnostics.filter((item) => item.status === 'loaded').length
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="runtime-status-widget">
      <h2 className="text-sm font-semibold text-foreground">Runtime Status</h2>
      <p className="mt-2 text-2xl font-semibold text-foreground">
        {loaded}/{admin.runtimeDiagnostics.length}
      </p>
      <p className="text-xs text-muted-foreground">Diagnostics loaded</p>
    </section>
  )
}
