import { useNavigationContext } from '@/app/providers/use-navigation-context'
import { useRuntimeContext } from '@/features/runtime/use-runtime-context'
import { LayoutDashboard } from '@/components/icons'

export function ShellHomePage() {
  const { workspace, personalization } = useRuntimeContext()
  const { navigation } = useNavigationContext()

  return (
    <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <LayoutDashboard className="h-5 w-5" aria-hidden />
        </div>
        <div>
          <h1 className="text-lg font-semibold">Application Shell</h1>
          <p className="text-sm text-muted-foreground">
            P1-001 foundation — business modules not implemented yet.
          </p>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium">Workspace runtime</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {workspace
              ? `Loaded v${workspace.runtime_version}`
              : 'Awaiting tenant scope and authenticated session'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium">Personalization runtime</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {personalization
              ? `Source: ${personalization.source}`
              : 'Will hydrate from GET /tenant/personalization/runtime'}
          </p>
        </section>
        <section className="rounded-lg border border-border bg-card p-4 md:col-span-2">
          <h2 className="text-sm font-medium">Navigation provider</h2>
          <p className="mt-2 text-xs text-muted-foreground">
            {Object.keys(navigation).length > 0
              ? 'Navigation metadata available from workspace runtime.'
              : 'Navigation overrides will merge when runtime is available.'}
          </p>
        </section>
      </div>
    </div>
  )
}
