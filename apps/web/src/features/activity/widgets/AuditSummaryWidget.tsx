import { useAuditLog } from '../hooks/useAuditLog'

export function AuditSummaryWidget() {
  const audit = useAuditLog({ per_page: 5 })
  const summary = audit.summary as Record<string, unknown> | null

  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="audit-summary-widget">
      <h2 className="mb-3 text-sm font-semibold text-foreground">Audit summary</h2>
      <dl className="grid grid-cols-2 gap-3 text-xs">
        <div>
          <dt className="text-muted-foreground">Total events</dt>
          <dd className="text-lg font-semibold text-foreground">{String(summary?.total_events ?? audit.items.length)}</dd>
        </div>
        <div>
          <dt className="text-muted-foreground">Recent entries</dt>
          <dd className="text-lg font-semibold text-foreground">{audit.items.length}</dd>
        </div>
      </dl>
    </section>
  )
}
