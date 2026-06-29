import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { LayoutDashboard } from '@/components/icons'
import { OrganizationSelectPage } from '@/features/auth/pages/OrganizationSelectPage'

export function ShellHomePage() {
  const runtime = useHydratedRuntime()

  return (
    <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
      <OrganizationSelectPage />
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <LayoutDashboard className="h-5 w-5" aria-hidden />
        </div>
        <div>
          <h1 className="text-lg font-semibold">HEOS Application Shell</h1>
          <p className="text-sm text-muted-foreground">
            Authenticated runtime is active. Business modules are not implemented in P1-002.
          </p>
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium">Workspace runtime</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.workspaceRuntime
              ? `Version ${runtime.workspaceRuntime.runtime_version}`
              : 'Unavailable'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium">Personalization runtime</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.personalizationRuntime
              ? `Source: ${runtime.personalizationRuntime.source}`
              : 'Unavailable'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4 md:col-span-2">
          <h2 className="text-sm font-medium">Permissions</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {runtime?.permissions.length ?? 0} permissions loaded from tenant context.
          </p>
        </section>
      </div>
    </div>
  )
}
