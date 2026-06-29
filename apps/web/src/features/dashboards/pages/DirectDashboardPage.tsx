import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'
import { fetchDashboardRender } from '@/api/endpoints/dashboards'
import {
  DashboardErrorState,
  DashboardLoadingState,
  DynamicDashboardRenderer,
} from '@/features/dashboards'
import { toDashboardQueryError } from '@/features/dashboards/core/dashboard-errors'

export function DirectDashboardPage() {
  const { moduleKey, dashboardKey } = useParams({ strict: false }) as {
    moduleKey: string
    dashboardKey: string
  }

  const query = useQuery({
    queryKey: ['dashboard-render', moduleKey, dashboardKey],
    queryFn: () => fetchDashboardRender(moduleKey, dashboardKey),
    enabled: Boolean(moduleKey && dashboardKey),
  })

  if (query.isLoading) {
    return <DashboardLoadingState />
  }

  if (query.isError || !query.data) {
    return (
      <DashboardErrorState
        message={query.error ? toDashboardQueryError(query.error).message : 'Unable to load dashboard.'}
      />
    )
  }

  return (
    <div className="mx-auto w-full max-w-7xl">
      <DynamicDashboardRenderer
        payload={query.data}
        binding={{
          moduleKey,
          dashboardKey,
          source: 'web',
          page: `/dashboards/${moduleKey}/${dashboardKey}`,
          auto_render: true,
          refresh_enabled: true,
          personalization_enabled: true,
        }}
        onRefresh={() => query.refetch()}
      />
    </div>
  )
}
