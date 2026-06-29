import type { DashboardLayout, DashboardLayoutItem } from '@/api/types/dashboards'
import type { ResolvedDashboardWidget } from '../types'

export interface GridPlacement {
  widgetKey: string
  columnStart: number
  columnSpan: number
  rowStart: number
  rowSpan: number
}

export function getLayoutColumns(layout: DashboardLayout | null | undefined): number {
  const columns = layout?.columns ?? layout?.metadata?.columns
  return typeof columns === 'number' && columns > 0 ? columns : 12
}

export function getLayoutGap(layout: DashboardLayout | null | undefined): string {
  const gap = layout?.gap ?? layout?.metadata?.gap
  if (typeof gap === 'number') {
    return `${gap}px`
  }

  if (typeof gap === 'string' && gap.trim() !== '') {
    return gap
  }

  return '1rem'
}

export function resolveLayoutItems(
  layout: DashboardLayout | null | undefined,
  widgets: ResolvedDashboardWidget[],
): GridPlacement[] {
  const layoutItems = layout?.items ?? []
  const itemMap = new Map(layoutItems.map((item) => [item.widget_key, item]))

  return widgets.map((widget, index) => {
    const item =
      itemMap.get(widget.widget_key) ??
      widget.layout ??
      widget.layoutItem ??
      ({
        widget_key: widget.widget_key,
        x: 0,
        y: index,
        width: 4,
        height: 1,
      } as DashboardLayoutItem)

    const columnSpan = Math.max(1, item.w ?? item.width ?? 4)
    const rowSpan = Math.max(1, item.h ?? item.height ?? 1)

    return {
      widgetKey: widget.widget_key,
      columnStart: Math.max(1, (item.x ?? 0) + 1),
      rowStart: Math.max(1, (item.y ?? index) + 1),
      columnSpan,
      rowSpan,
    }
  })
}

export function buildFallbackLayout(widgets: ResolvedDashboardWidget[]): GridPlacement[] {
  return widgets.map((widget, index) => ({
    widgetKey: widget.widget_key,
    columnStart: 1,
    rowStart: index + 1,
    columnSpan: 4,
    rowSpan: 1,
  }))
}

export function getGridStyle(placement: GridPlacement): Record<string, string | number> {
  return {
    gridColumn: `${placement.columnStart} / span ${placement.columnSpan}`,
    gridRow: `${placement.rowStart} / span ${placement.rowSpan}`,
  }
}
