import { useQuery } from '@tanstack/react-query'
import { fetchDashboardRender } from '@/api/endpoints/dashboards'
import { normalizeDashboardBindingContext } from '@/api/types/dashboards'
import type { UiComponent } from '@/api/types/ui'
import {
  DashboardErrorState,
  DashboardLoadingState,
  DynamicDashboardRenderer,
} from '@/features/dashboards'
import { toDashboardQueryError } from '@/features/dashboards/core/dashboard-errors'
import { useComponentBinding } from '../hooks/useComponentBinding'
import { useOptionalRendererContext } from '../hooks/useRendererContext'

interface DashboardBindingRendererProps {
  component: UiComponent
}

function bindingRenderEnabled(config: Record<string, unknown>): boolean {
  return config.auto_render === true || config.autoRender === true || config.render_enabled === true
}

export function DashboardBindingRenderer({
  component,
}: DashboardBindingRendererProps) {
  const binding = useComponentBinding(component)
  const rendererContext = useOptionalRendererContext()
  const shouldRender = bindingRenderEnabled(binding?.config ?? {})
  const query = useQuery({
    queryKey: ['dashboard-render', binding?.moduleKey, binding?.resourceKey],
    queryFn: () =>
      fetchDashboardRender(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey && shouldRender),
  })

  if (!binding) {
    return (
      <div
        className="text-sm text-muted-foreground"
        data-testid="dashboard-binding-missing"
      >
        Dashboard binding unavailable
      </div>
    )
  }

  if (!shouldRender) {
    return (
      <div
        className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground"
        data-testid="dashboard-binding-renderer"
      >
        Dashboard binding configured without auto render.
      </div>
    )
  }

  if (query.isLoading) {
    return (
      <div data-testid="dashboard-binding-loading">
        <DashboardLoadingState />
      </div>
    )
  }

  if (query.isError || !query.data) {
    return (
      <div data-testid="dashboard-binding-error">
        <DashboardErrorState
          message={
            query.error
              ? toDashboardQueryError(query.error).message
              : 'Unable to load dashboard metadata.'
          }
        />
      </div>
    )
  }

  const dashboardBinding = normalizeDashboardBindingContext(
    {
      ...binding.config,
      ...component.binding_config,
      page: rendererContext?.pageKey,
      binding: component.component_key,
      auto_render: shouldRender,
    },
    binding.moduleKey,
    binding.resourceKey,
  )

  return (
    <div data-testid="dashboard-binding-renderer">
      <DynamicDashboardRenderer
        payload={query.data}
        binding={dashboardBinding}
        onRefresh={() => query.refetch()}
      />
    </div>
  )
}
