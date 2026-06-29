import { useQuery } from '@tanstack/react-query'
import { fetchUiPages } from '@/api/endpoints/ui'
import { LayoutDashboard } from '@/components/icons'
import {
  DashboardFavorites,
  DashboardMetricCard,
  DashboardQuickActions,
  DashboardRecentItems,
  useHomePersonalization,
} from '@/features/dashboards'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { OrganizationSelectPage } from '@/features/auth/pages/OrganizationSelectPage'

import type { HydratedRuntimeBundle } from '@/api/types/runtime'

function countNavigationItems(menus: HydratedRuntimeBundle['navigationMenus']): number {
  return menus.reduce((total, menu) => {
    return (
      total +
      menu.groups.reduce((groupTotal, group) => groupTotal + group.items.length, 0)
    )
  }, 0)
}

function mapPersonalizationItems(items: Array<Record<string, unknown>>) {
  return items.map((item) => ({
    label: typeof item.label === 'string' ? item.label : typeof item.title === 'string' ? item.title : undefined,
    route: typeof item.route === 'string' ? item.route : undefined,
    action_key: typeof item.action_key === 'string' ? item.action_key : undefined,
  }))
}

export function ShellHomePage() {
  const runtime = useHydratedRuntime()
  const personalization = useHomePersonalization()
  const pagesQuery = useQuery({
    queryKey: ['ui-pages-summary'],
    queryFn: fetchUiPages,
    enabled: Boolean(runtime),
  })

  const applicationCount =
    runtime?.workspaceRuntime?.active_applications?.length ?? 0
  const pagesCount = pagesQuery.data?.length ?? 0
  const navigationCount = countNavigationItems(runtime?.navigationMenus ?? [])
  const organizationName = runtime?.organization?.name ?? 'Unknown organization'
  const workspaceName = runtime?.workspace?.name ?? 'Unknown workspace'
  const userName = runtime?.user?.display_name ?? runtime?.user?.email ?? 'User'

  return (
    <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <OrganizationSelectPage />

      <section className="rounded-lg border border-border bg-card p-5">
        <div className="flex items-start gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <LayoutDashboard className="h-5 w-5" aria-hidden />
          </div>
          <div>
            <h1 className="text-lg font-semibold text-foreground">Welcome back, {userName}</h1>
            <p className="text-sm text-muted-foreground">
              HEOS home experience powered by hydrated runtime and personalization metadata.
            </p>
          </div>
        </div>
      </section>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <DashboardMetricCard title="Applications" value={String(applicationCount)} />
        <DashboardMetricCard title="UI pages" value={pagesQuery.isLoading ? '…' : String(pagesCount)} />
        <DashboardMetricCard title="Navigation items" value={String(navigationCount)} />
        <DashboardMetricCard
          title="Notifications"
          value={String(runtime?.unreadNotificationCount ?? 0)}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Organization & workspace</h2>
          <dl className="mt-3 space-y-2 text-xs text-muted-foreground">
            <div>
              <dt className="font-medium text-foreground">Organization</dt>
              <dd>{organizationName}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Workspace</dt>
              <dd>{workspaceName}</dd>
            </div>
          </dl>
        </section>

        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Runtime status</h2>
          <dl className="mt-3 space-y-2 text-xs text-muted-foreground">
            <div>
              <dt className="font-medium text-foreground">Runtime</dt>
              <dd>{runtime ? 'Hydrated runtime loaded' : 'Runtime unavailable'}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Navigation source</dt>
              <dd>{runtime?.source ?? 'unknown'}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Theme source</dt>
              <dd>{runtime?.themeRuntime?.source ?? 'Unavailable'}</dd>
            </div>
            <div>
              <dt className="font-medium text-foreground">Personalization source</dt>
              <dd>{personalization.source}</dd>
            </div>
          </dl>
        </section>

        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Platform capabilities</h2>
          <ul className="mt-3 space-y-1 text-xs text-muted-foreground">
            <li>Metadata pages, dynamic forms, and dynamic tables are active.</li>
            <li>Dashboard renderer supports widget grids and placeholders.</li>
            <li>{runtime?.permissions.length ?? 0} permissions loaded from tenant context.</li>
          </ul>
        </section>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <section className="rounded-lg border border-border bg-card p-4">
          <DashboardFavorites
            title="Favorites"
            items={mapPersonalizationItems(personalization.favorites as Array<Record<string, unknown>>)}
          />
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <DashboardRecentItems
            title="Recent items"
            items={mapPersonalizationItems(personalization.recentItems as Array<Record<string, unknown>>)}
          />
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <DashboardQuickActions
            title="Shortcuts"
            actions={mapPersonalizationItems(personalization.shortcuts as Array<Record<string, unknown>>)}
          />
        </section>
        <section className="rounded-lg border border-border bg-card p-4">
          <DashboardQuickActions
            title="Quick actions"
            actions={mapPersonalizationItems(personalization.quickActions as Array<Record<string, unknown>>)}
          />
        </section>
      </div>
    </div>
  )
}
