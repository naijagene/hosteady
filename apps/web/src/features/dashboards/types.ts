import type {
  DashboardAction,
  DashboardDefinition,
  DashboardFilter,
  DashboardLayout,
  DashboardLayoutItem,
  DashboardWidget,
  DashboardWidgetData,
} from '@/api/types/dashboards'

export interface NormalizedDashboardModel {
  definition: DashboardDefinition
  widgets: DashboardWidget[]
  layout: DashboardLayout | null
  filters: DashboardFilter[]
  actions: DashboardAction[]
  widgetDataMap: Map<string, DashboardWidgetData>
}

export interface DashboardPersonalizationState {
  hiddenWidgetKeys: Set<string>
  collapsedWidgetKeys: Set<string>
  widgetOrder: string[]
  layoutDensity: 'comfortable' | 'compact'
}

export interface DashboardFilterState {
  values: Record<string, unknown>
}

export interface ResolvedDashboardWidget extends DashboardWidget {
  data?: DashboardWidgetData
  layoutItem?: DashboardLayoutItem | null
  hidden?: boolean
  collapsed?: boolean
}
