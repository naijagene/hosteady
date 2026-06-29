import type { DashboardWidget } from '@/api/types/dashboards'
import type { ResolvedDashboardWidget } from '../types'

const widgetTypeAliases: Record<string, string> = {
  kpi_card: 'metric',
  kpi: 'metric',
  metric_card: 'metric',
  number_card: 'metric',
  chart_card: 'chart',
  line_chart: 'chart',
  bar_chart: 'chart',
  pie_chart: 'chart',
  table_widget: 'table',
  data_table: 'table',
  report_widget: 'report',
  notification_feed: 'notification',
  notifications: 'notification',
  activity_feed: 'activity',
  activity: 'activity',
  quick_actions: 'quick_actions',
  quick_action: 'quick_actions',
  recent_items: 'recent_items',
  recents: 'recent_items',
  favorites: 'favorites',
  favourite: 'favorites',
}

export function normalizeWidgetType(widgetType: string | undefined | null): string {
  const normalized = (widgetType ?? 'custom').toLowerCase().trim()

  if (widgetTypeAliases[normalized]) {
    return widgetTypeAliases[normalized]
  }

  if (['metric', 'chart', 'table', 'report', 'notification', 'activity', 'quick_actions', 'recent_items', 'favorites'].includes(normalized)) {
    return normalized
  }

  if (normalized.includes('chart')) {
    return 'chart'
  }

  return normalized === 'custom' ? 'custom' : 'custom'
}

export function resolveDashboardWidgets(
  widgets: DashboardWidget[],
  options?: {
    hiddenKeys?: Set<string>
    order?: string[]
  },
): ResolvedDashboardWidget[] {
  const visible = widgets.filter((widget) => !options?.hiddenKeys?.has(widget.widget_key))

  if (!options?.order || options.order.length === 0) {
    return visible.sort((left, right) => (left.sort_order ?? 0) - (right.sort_order ?? 0))
  }

  const orderMap = new Map(options.order.map((key, index) => [key, index]))

  return [...visible].sort((left, right) => {
    const leftIndex = orderMap.get(left.widget_key)
    const rightIndex = orderMap.get(right.widget_key)

    if (leftIndex === undefined && rightIndex === undefined) {
      return (left.sort_order ?? 0) - (right.sort_order ?? 0)
    }

    if (leftIndex === undefined) {
      return 1
    }

    if (rightIndex === undefined) {
      return -1
    }

    return leftIndex - rightIndex
  })
}

export function attachWidgetData(
  widgets: ResolvedDashboardWidget[],
  widgetDataMap: Map<string, import('@/api/types/dashboards').DashboardWidgetData>,
): ResolvedDashboardWidget[] {
  return widgets.map((widget) => ({
    ...widget,
    data: widgetDataMap.get(widget.widget_key),
  }))
}
