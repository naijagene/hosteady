import { useQuery } from '@tanstack/react-query'
import { fetchDashboardRender } from '@/api/endpoints/dashboards'
import type { UiComponent } from '@/api/types/ui'
import { useComponentBinding } from '../hooks/useComponentBinding'

interface DashboardBindingRendererProps {
  component: UiComponent
}

export function DashboardBindingRenderer({
  component,
}: DashboardBindingRendererProps) {
  const binding = useComponentBinding(component)
  const query = useQuery({
    queryKey: ['dashboard-render', binding?.moduleKey, binding?.resourceKey],
    queryFn: () =>
      fetchDashboardRender(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey),
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

  if (query.isLoading) {
    return (
      <div
        className="text-sm text-muted-foreground"
        data-testid="dashboard-binding-loading"
      >
        Loading dashboard…
      </div>
    )
  }

  const widgets = query.data?.widgets ?? []

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="dashboard-binding-renderer"
    >
      <h4 className="text-sm font-medium text-foreground">
        {query.data?.dashboard.name ?? component.name}
      </h4>
      <div className="mt-4 grid gap-3 sm:grid-cols-2">
        {widgets.length === 0 ? (
          <p className="text-xs text-muted-foreground">No widgets configured</p>
        ) : (
          widgets.map((widget) => (
            <div
              key={widget.widget_key}
              className="rounded-md border border-dashed border-border bg-muted/20 p-3 text-xs text-muted-foreground"
            >
              {widget.label}
            </div>
          ))
        )}
      </div>
    </section>
  )
}
