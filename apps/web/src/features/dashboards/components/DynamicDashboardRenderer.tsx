import type { DashboardBindingContext, DashboardRenderPayload } from '@/api/types/dashboards'
import { useDashboardActions } from '../hooks/useDashboardActions'
import { useDashboardFilters } from '../hooks/useDashboardFilters'
import { useDashboardRender } from '../hooks/useDashboardRender'
import { DashboardEmptyState } from './DashboardEmptyState'
import { DashboardFilterBar } from './DashboardFilterBar'
import { DashboardGrid } from './DashboardGrid'
import { DashboardToolbar } from './DashboardToolbar'

interface DynamicDashboardRendererProps {
  payload: DashboardRenderPayload
  binding?: DashboardBindingContext
  onRefresh?: () => void
}

export function DynamicDashboardRenderer({
  payload,
  binding,
  onRefresh,
}: DynamicDashboardRendererProps) {
  const { title, description, layout, widgets, filters, actions, personalization } =
    useDashboardRender({ payload, binding })
  const filterState = useDashboardFilters(filters)
  const actionState = useDashboardActions({ onRefresh })

  return (
    <section
      className="overflow-hidden rounded-lg border border-border bg-card"
      data-testid="dynamic-dashboard-renderer"
    >
      <DashboardToolbar
        title={title}
        description={description}
        actions={actions}
        onAction={actionState.handleAction}
        message={actionState.message}
      />
      <DashboardFilterBar
        filters={filters}
        values={filterState.values}
        onChange={filterState.setFilterValue}
        onClear={filterState.clearFilters}
      />
      {widgets.length === 0 ? (
        <div className="p-4">
          <DashboardEmptyState message={binding?.empty_state_message} />
        </div>
      ) : (
        <DashboardGrid
          layout={layout}
          widgets={widgets}
          density={personalization.layoutDensity}
        />
      )}
    </section>
  )
}
