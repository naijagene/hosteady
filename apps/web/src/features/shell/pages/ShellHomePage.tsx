import { useQuery } from '@tanstack/react-query'
import { fetchUiPages } from '@/api/endpoints/ui'
import {
  fetchApprovals,
  fetchHumanTaskInbox,
  fetchWorkflowInstances,
} from '@/api/endpoints/workflows'
import { LayoutDashboard } from '@/components/icons'
import {
  DashboardFavorites,
  DashboardMetricCard,
  DashboardQuickActions,
  DashboardRecentItems,
  useHomePersonalization,
} from '@/features/dashboards'
import { ActivityFeedWidget, AuditSummaryWidget, SystemHistoryWidget } from '@/features/activity'
import {
  PlatformStatusWidget,
  RuntimeStatusWidget,
  FeatureSummaryWidget,
  WorkspaceStatusWidget,
} from '@/features/admin'
import { AlphaReadinessWidget } from '@/features/alpha'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { OrganizationSelectPage } from '@/features/auth/pages/OrganizationSelectPage'

import { countNavigationItems } from '@/features/runtime/core/normalize-navigation'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

function countNavigationItemsFromRuntime(menus: HydratedRuntimeBundle['navigationMenus']): number {
  return countNavigationItems(menus)
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

  const assignedTasksQuery = useQuery({
    queryKey: ['home-assigned-tasks'],
    queryFn: () => fetchHumanTaskInbox('assigned', 25),
    enabled: Boolean(runtime),
  })

  const pendingApprovalsQuery = useQuery({
    queryKey: ['home-pending-approvals'],
    queryFn: () => fetchApprovals({ status: 'pending', per_page: 25 }),
    enabled: Boolean(runtime),
  })

  const failedWorkflowsQuery = useQuery({
    queryKey: ['home-failed-workflows'],
    queryFn: () => fetchWorkflowInstances({ status: 'failed', per_page: 25 }),
    enabled: Boolean(runtime),
  })

  const applicationCount =
    runtime?.workspaceRuntime?.active_applications?.length ?? 0
  const pagesCount = pagesQuery.data?.length ?? 0
  const navigationCount = countNavigationItemsFromRuntime(runtime?.navigationMenus ?? [])
  const assignedTasksCount = assignedTasksQuery.data?.length ?? 0
  const pendingApprovalsCount = pendingApprovalsQuery.data?.length ?? 0
  const failedWorkflowCount = failedWorkflowsQuery.data?.length ?? 0
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
        <DashboardMetricCard title="Assigned tasks" value={assignedTasksQuery.isLoading ? '…' : String(assignedTasksCount)} />
        <DashboardMetricCard title="Pending approvals" value={pendingApprovalsQuery.isLoading ? '…' : String(pendingApprovalsCount)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <DashboardMetricCard title="Navigation items" value={String(navigationCount)} />
        <DashboardMetricCard title="Failed workflows" value={failedWorkflowsQuery.isLoading ? '…' : String(failedWorkflowCount)} />
        <DashboardMetricCard
          title="Notifications"
          value={String(runtime?.unreadNotificationCount ?? 0)}
        />
        <DashboardMetricCard title="Permissions" value={String(runtime?.permissions.length ?? 0)} />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <PlatformStatusWidget />
        <WorkspaceStatusWidget />
        <RuntimeStatusWidget />
        <FeatureSummaryWidget />
      </div>

      <AlphaReadinessWidget />

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
            <li>Workflow inbox, approvals, and instance views are available under /workflows.</li>
            <li>{runtime?.permissions.length ?? 0} permissions loaded from tenant context.</li>
          </ul>
        </section>

        <section className="rounded-lg border border-border bg-card p-4">
          <ActivityFeedWidget title="Recent activity" binding={{ per_page: 4, mode: 'compact' }} />
        </section>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <AuditSummaryWidget />
        <SystemHistoryWidget />
        <section className="rounded-lg border border-border bg-card p-4">
          <h2 className="text-sm font-medium text-foreground">Security activity</h2>
          <p className="mt-3 text-xs text-muted-foreground">
            Security audit events will appear here when `security.audit.read` data is available.
          </p>
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
