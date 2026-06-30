import { Link } from '@tanstack/react-router'
import { AlphaChecklist } from './AlphaChecklist'
import { AlphaHealthCard } from './AlphaHealthCard'
import { AlphaStatusBadge } from './AlphaStatusBadge'
import { useAlphaHealth } from '../hooks/useAlphaHealth'
import { getAlphaStatusLabel } from '../core/alpha-health'

export function AlphaHealthPage() {
  const health = useAlphaHealth()

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-6" data-testid="alpha-health-page">
      <section className="rounded-lg border border-border bg-card p-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-lg font-semibold text-foreground">Platform Alpha Health</h1>
            <p className="text-sm text-muted-foreground">
              Internal readiness snapshot for HEOS Backend v1.0 + Live Experience validation.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs text-muted-foreground">Overall status</span>
            <AlphaStatusBadge status={health.status} />
          </div>
        </div>
        <p className="mt-3 text-xs text-muted-foreground">
          Status: {getAlphaStatusLabel(health.status)} · Validated at {new Date(health.api.validated_at).toLocaleString()}
        </p>
      </section>

      <div className="grid gap-4 lg:grid-cols-2">
        <AlphaHealthCard title="Runtime checks" testId="alpha-runtime-card">
          <AlphaChecklist items={health.runtime} mode="runtime" />
        </AlphaHealthCard>

        <AlphaHealthCard title="Frontend features" testId="alpha-features-card">
          <AlphaChecklist items={health.features} mode="features" />
        </AlphaHealthCard>
      </div>

      <AlphaHealthCard title="API diagnostics" testId="alpha-api-card">
        <dl className="grid gap-3 text-xs md:grid-cols-2">
          <div>
            <dt className="font-medium text-foreground">API base URL</dt>
            <dd className="text-muted-foreground">{health.api.base_url}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Token present</dt>
            <dd className="text-muted-foreground">{health.api.token_present ? 'Yes' : 'No'}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Tenant headers present</dt>
            <dd className="text-muted-foreground">{health.api.tenant_headers_present ? 'Yes' : 'No'}</dd>
          </div>
          <div>
            <dt className="font-medium text-foreground">Runtime endpoint status</dt>
            <dd className="text-muted-foreground">{health.api.runtime_endpoint_status ?? 'Not checked'}</dd>
          </div>
        </dl>
      </AlphaHealthCard>

      <section className="rounded-lg border border-dashed border-border bg-muted/30 p-4 text-xs text-muted-foreground">
        <p>
          Manual smoke test checklist: see <code>docs/alpha/HEOS_ALPHA_SMOKE_TEST.md</code> in the repository.
        </p>
        <div className="mt-3 flex flex-wrap gap-3">
          <Link to="/" className="text-primary underline-offset-4 hover:underline">
            Back to home
          </Link>
          <Link to="/admin" className="text-primary underline-offset-4 hover:underline">
            Administration console
          </Link>
        </div>
      </section>
    </div>
  )
}
