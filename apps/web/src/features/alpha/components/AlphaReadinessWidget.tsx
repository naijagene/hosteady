import { Link } from '@tanstack/react-router'
import { AlphaStatusBadge } from './AlphaStatusBadge'
import { useAlphaHealth } from '../hooks/useAlphaHealth'
import { getAlphaStatusLabel } from '../core/alpha-health'

export function AlphaReadinessWidget() {
  const health = useAlphaHealth()

  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="alpha-readiness-widget">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-sm font-medium text-foreground">Platform Alpha</h2>
          <p className="mt-1 text-xs text-muted-foreground">
            Runtime {getAlphaStatusLabel(health.status).toLowerCase()} for internal validation.
          </p>
        </div>
        <AlphaStatusBadge status={health.status} />
      </div>
      <ul className="mt-3 space-y-1 text-xs text-muted-foreground">
        <li>Smoke test: docs/alpha/HEOS_ALPHA_SMOKE_TEST.md</li>
      </ul>
      <div className="mt-3 flex flex-wrap gap-3 text-xs">
        <Link to="/alpha/health" className="text-primary underline-offset-4 hover:underline">
          Alpha health
        </Link>
        <Link to="/admin" className="text-primary underline-offset-4 hover:underline">
          Admin console
        </Link>
      </div>
    </section>
  )
}
