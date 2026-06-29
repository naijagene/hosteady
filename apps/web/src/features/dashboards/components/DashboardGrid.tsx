import type { DashboardLayout } from '@/api/types/dashboards'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import {
  buildFallbackLayout,
  getGridStyle,
  getLayoutColumns,
  getLayoutGap,
  resolveLayoutItems,
} from '../core/dashboard-layout'
import type { ResolvedDashboardWidget } from '../types'
import { DashboardWidgetRenderer } from './DashboardWidgetRenderer'

interface DashboardGridProps {
  layout: DashboardLayout | null
  widgets: Array<ResolvedDashboardWidget & { widgetType: string }>
  density?: 'comfortable' | 'compact'
}

export function DashboardGrid({ layout, widgets, density = 'comfortable' }: DashboardGridProps) {
  const columns = getLayoutColumns(layout)
  const gap = getLayoutGap(layout)
  const placements =
    layout && (layout.items?.length ?? 0) > 0
      ? resolveLayoutItems(layout, widgets)
      : buildFallbackLayout(widgets)
  const placementMap = new Map(placements.map((item) => [item.widgetKey, item]))

  return (
    <div
      className={`grid p-4 ${density === 'compact' ? 'gap-3' : 'gap-4'}`}
      data-testid="dashboard-grid"
      role="region"
      aria-label="Dashboard widgets"
      style={{
        gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
        gap,
      }}
    >
      {widgets.map((widget) => {
        const placement = placementMap.get(widget.widget_key)

        return (
          <div key={widget.widget_key} style={placement ? getGridStyle(placement) : undefined}>
            <ErrorBoundary
              fallback={
                <div className="rounded-lg border border-border bg-card p-4 text-xs text-muted-foreground">
                  Widget failed to render safely.
                </div>
              }
            >
              <DashboardWidgetRenderer widget={widget} widgetType={widget.widgetType} />
            </ErrorBoundary>
          </div>
        )
      })}
    </div>
  )
}
