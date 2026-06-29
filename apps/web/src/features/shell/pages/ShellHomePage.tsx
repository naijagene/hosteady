import { useQuery } from '@tanstack/react-query'
import { fetchUiPages } from '@/api/endpoints/ui'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { LayoutDashboard } from '@/components/icons'
import { OrganizationSelectPage } from '@/features/auth/pages/OrganizationSelectPage'

export function ShellHomePage() {
  const runtime = useHydratedRuntime()
  const pagesQuery = useQuery({
    queryKey: ['ui-pages-summary'],
    queryFn: fetchUiPages,
    enabled: Boolean(runtime),
  })

  const applicationCount =
    runtime?.workspaceRuntime?.active_applications?.length ?? 0
  const pagesCount = pagesQuery.data?.length ?? 0

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
      <OrganizationSelectPage />
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <LayoutDashboard className="h-5 w-5" aria-hidden />
        </div>
        <div>
          <h1 className="text-lg font-semibold text-foreground">HEOS Application Shell</h1>
          <p className="text-sm text-muted-foreground">
            Metadata renderer foundation is active. Pages render from backend UI metadata.
          </p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Runtime status</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime ? 'Hydrated runtime loaded' : 'Runtime unavailable'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Available pages</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {pagesQuery.isLoading ? 'Loading…' : `${pagesCount} UI pages`}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Applications</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {applicationCount} active applications
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Navigation source</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.source ?? 'unknown'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Theme source</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.themeRuntime?.source ?? 'Unavailable'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Personalization source</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.personalizationRuntime?.source ?? 'Unavailable'}
          </p>
        </section>
      </div>

      <section className="rounded-lg border border-border bg-card p-4">
        <h2 className="text-sm font-medium text-foreground">Quick actions</h2>
        <div className="mt-3 flex flex-wrap gap-2">
          {['Open page', 'Browse tables', 'View dashboards', 'Run report'].map(
            (label) => (
              <button
                key={label}
                type="button"
                disabled
                className="rounded-md border border-border px-3 py-1 text-xs text-muted-foreground"
              >
                {label} (placeholder)
              </button>
            ),
          )}
        </div>
      </section>

      <section className="rounded-lg border border-border bg-card p-4">
        <h2 className="text-sm font-medium text-foreground">Permissions</h2>
        <p className="mt-2 text-xs text-muted-foreground">
          {runtime?.permissions.length ?? 0} permissions loaded from tenant context.
        </p>
      </section>
    </div>
  )
}
