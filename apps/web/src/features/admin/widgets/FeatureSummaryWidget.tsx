import { useAdminConsole } from '../hooks/useAdminConsole'

export function FeatureSummaryWidget() {
  const admin = useAdminConsole()
  const counts = admin.platformOverview.feature_counts ?? {}
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="feature-summary-widget">
      <h2 className="text-sm font-semibold text-foreground">Feature Summary</h2>
      <dl className="mt-3 grid grid-cols-2 gap-3 text-xs">
        {Object.entries(counts).slice(0, 4).map(([key, value]) => (
          <div key={key}>
            <dt className="text-muted-foreground">{key}</dt>
            <dd className="text-lg font-semibold text-foreground">{String(value)}</dd>
          </div>
        ))}
      </dl>
    </section>
  )
}
