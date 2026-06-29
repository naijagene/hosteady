import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface DashboardMetric {
  key: string
  label: string
  format?: string
  data_source_type?: string | null
  data_source_config?: MetadataRecord
  metadata?: MetadataRecord
}

export interface DashboardDataset {
  key: string
  label?: string
  data?: unknown[]
  metadata?: MetadataRecord
}

export interface DashboardChart {
  type: string
  datasets?: DashboardDataset[]
  labels?: string[]
  metadata?: MetadataRecord
}

export interface DashboardLayoutItem {
  widget_key: string
  x?: number
  y?: number
  w?: number
  h?: number
  width?: number
  height?: number
  min_w?: number
  min_h?: number
  metadata?: MetadataRecord
}

export interface DashboardLayout {
  columns?: number
  rows?: number
  gap?: number | string
  items?: DashboardLayoutItem[]
  breakpoints?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface DashboardFilter {
  filter_key: string
  label: string
  filter_type: string
  operator?: string
  field_key?: string
  value?: unknown
  options?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface DashboardAction {
  action_key: string
  label: string
  action_type: string
  permission?: string | null
  metadata?: MetadataRecord
}

export interface DashboardView {
  view_key: string
  label: string
  layout?: DashboardLayout | null
  metadata?: MetadataRecord
}

export interface DashboardWidget {
  widget_key: string
  label: string
  name?: string
  description?: string | null
  widget_type: string
  chart_type?: string | null
  metric?: DashboardMetric | null
  layout?: DashboardLayoutItem | null
  permission?: string | null
  sort_order?: number
  metadata?: MetadataRecord
}

export interface DashboardWidgetData {
  widget_key: string
  value?: unknown
  chart?: DashboardChart | null
  rows?: MetadataRecord[]
  metric?: DashboardMetric | null
  metadata?: MetadataRecord
}

export interface DashboardStatistics {
  definitions?: number
  widgets?: number
  views?: number
  registered_modules?: string[]
  metadata?: MetadataRecord
}

export interface DashboardDefinition {
  public_id?: string
  module_key: string
  dashboard_key: string
  name: string
  description?: string | null
  entity_key?: string | null
  widgets?: DashboardWidget[]
  filters?: DashboardFilter[]
  actions?: DashboardAction[]
  views?: DashboardView[]
  layout?: DashboardLayout | null
  metadata?: MetadataRecord
}

export interface DashboardRenderPayload {
  dashboard: DashboardDefinition
  layout?: DashboardLayout | null
  widgets: DashboardWidget[]
  widget_data?: DashboardWidgetData[]
  datasets?: DashboardDataset[]
  metrics?: DashboardMetric[]
  filters?: DashboardFilter[]
  actions?: DashboardAction[]
  statistics?: DashboardStatistics | null
  permissions?: MetadataRecord | string[]
  runtime_context?: MetadataRecord
  metadata?: MetadataRecord
}

export interface DashboardBindingContext {
  moduleKey: string
  dashboardKey: string
  source?: string
  page?: string
  binding?: string
  auto_render?: boolean
  filters?: DashboardFilter[]
  refresh_enabled?: boolean
  personalization_enabled?: boolean
  empty_state_message?: string
}

function normalizeDashboardMetric(raw: unknown): DashboardMetric {
  const data = asRecord(raw)
  return {
    key: asString(data.key ?? data.metric_key),
    label: asString(data.label, 'Metric'),
    format: asString(data.format, 'number'),
    data_source_type:
      typeof (data.data_source_type ?? data.dataSourceType) === 'string'
        ? ((data.data_source_type ?? data.dataSourceType) as string)
        : null,
    data_source_config: asRecord(data.data_source_config ?? data.dataSourceConfig),
    metadata: asRecord(data.metadata),
  }
}

function normalizeDashboardDataset(raw: unknown): DashboardDataset {
  const data = asRecord(raw)
  return {
    key: asString(data.key ?? data.dataset_key),
    label: asString(data.label),
    data: asArray(data.data),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardChart(raw: unknown): DashboardChart {
  const data = asRecord(raw)
  return {
    type: asString(data.type ?? data.chart_type, 'line'),
    datasets: asArray(data.datasets).map(normalizeDashboardDataset),
    labels: asArray<string>(data.labels).map((label) => asString(label)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardLayoutItem(raw: unknown): DashboardLayoutItem {
  const data = asRecord(raw)
  const width =
    typeof (data.width ?? data.w) === 'number'
      ? ((data.width ?? data.w) as number)
      : undefined
  const height =
    typeof (data.height ?? data.h) === 'number'
      ? ((data.height ?? data.h) as number)
      : undefined

  return {
    widget_key: asString(data.widget_key ?? data.widgetKey ?? data.key),
    x: typeof data.x === 'number' ? data.x : 0,
    y: typeof data.y === 'number' ? data.y : 0,
    w: width,
    h: height,
    width,
    height,
    min_w: typeof (data.min_w ?? data.minW) === 'number' ? ((data.min_w ?? data.minW) as number) : undefined,
    min_h: typeof (data.min_h ?? data.minH) === 'number' ? ((data.min_h ?? data.minH) as number) : undefined,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardLayout(raw: unknown): DashboardLayout {
  const data = asRecord(raw)
  return {
    columns: typeof (data.columns ?? data.column_count) === 'number' ? ((data.columns ?? data.column_count) as number) : undefined,
    rows: typeof data.rows === 'number' ? data.rows : undefined,
    gap:
      typeof data.gap === 'number' || typeof data.gap === 'string'
        ? (data.gap as number | string)
        : undefined,
    items: asArray(data.items).map(normalizeDashboardLayoutItem),
    breakpoints: asArray(data.breakpoints).map((item) => asRecord(item)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardFilter(raw: unknown): DashboardFilter {
  const data = asRecord(raw)
  return {
    filter_key: asString(data.filter_key ?? data.filterKey ?? data.field_key ?? data.key),
    label: asString(data.label ?? data.name, 'Filter'),
    filter_type: asString(data.filter_type ?? data.filterType ?? data.type, 'text'),
    operator: asString(data.operator, 'equals'),
    field_key: asString(data.field_key ?? data.fieldKey ?? data.key),
    value: data.value,
    options: asArray(data.options).map((item) => asRecord(item)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardAction(raw: unknown): DashboardAction {
  const data = asRecord(raw)
  return {
    action_key: asString(data.action_key ?? data.actionKey ?? data.key),
    label: asString(data.label, 'Action'),
    action_type: asString(data.action_type ?? data.actionType ?? data.type, 'custom'),
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardView(raw: unknown): DashboardView {
  const data = asRecord(raw)
  return {
    view_key: asString(data.view_key ?? data.viewKey ?? data.key ?? data.public_id),
    label: asString(data.label ?? data.name, 'View'),
    layout: data.layout ? normalizeDashboardLayout(data.layout) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardWidget(raw: unknown): DashboardWidget {
  const data = asRecord(raw)
  return {
    widget_key: asString(data.widget_key ?? data.widgetKey ?? data.key),
    label: asString(data.label ?? data.name, 'Widget'),
    name: asString(data.name ?? data.label),
    description: typeof data.description === 'string' ? data.description : null,
    widget_type: asString(data.widget_type ?? data.widgetType ?? data.type, 'custom'),
    chart_type:
      typeof (data.chart_type ?? data.chartType) === 'string'
        ? ((data.chart_type ?? data.chartType) as string)
        : null,
    metric: data.metric ? normalizeDashboardMetric(data.metric) : null,
    layout: data.layout ? normalizeDashboardLayoutItem(data.layout) : null,
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
    sort_order: typeof data.sort_order === 'number' ? data.sort_order : undefined,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardWidgetData(raw: unknown): DashboardWidgetData {
  const data = asRecord(raw)
  return {
    widget_key: asString(data.widget_key ?? data.widgetKey ?? data.key),
    value: data.value,
    chart: data.chart ? normalizeDashboardChart(data.chart) : null,
    rows: asArray(data.rows).map((row) => asRecord(row)),
    metric: data.metric ? normalizeDashboardMetric(data.metric) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardStatistics(raw: unknown): DashboardStatistics {
  const data = asRecord(raw)
  return {
    definitions: typeof data.definitions === 'number' ? data.definitions : undefined,
    widgets: typeof data.widgets === 'number' ? data.widgets : undefined,
    views: typeof data.views === 'number' ? data.views : undefined,
    registered_modules: asArray<string>(data.registered_modules ?? data.registeredModules).map(
      (item) => asString(item),
    ),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardDefinition(raw: unknown): DashboardDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    dashboard_key: asString(data.dashboard_key ?? data.dashboardKey ?? data.key),
    name: asString(data.name ?? data.label, 'Dashboard'),
    description: typeof data.description === 'string' ? data.description : null,
    entity_key:
      typeof (data.entity_key ?? data.entityKey) === 'string'
        ? ((data.entity_key ?? data.entityKey) as string)
        : null,
    widgets: asArray(data.widgets).map(normalizeDashboardWidget),
    filters: asArray(data.filters).map(normalizeDashboardFilter),
    actions: asArray(data.actions).map(normalizeDashboardAction),
    views: asArray(data.views).map(normalizeDashboardView),
    layout: data.layout ? normalizeDashboardLayout(data.layout) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardRenderPayload(raw: unknown): DashboardRenderPayload {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const dashboardSource = asRecord(data.dashboard ?? data.definition)
  const dashboard =
    Object.keys(dashboardSource).length > 0
      ? normalizeDashboardDefinition(dashboardSource)
      : normalizeDashboardDefinition({
          module_key: metadata.module_key ?? metadata.moduleKey,
          dashboard_key: metadata.dashboard_key ?? metadata.dashboardKey,
          name: metadata.name ?? metadata.label,
          description: metadata.description,
          public_id: metadata.public_id ?? metadata.publicId,
          widgets: data.widgets,
          filters: data.filters,
          actions: data.actions,
          layout: data.layout,
          metadata,
        })

  const widgets =
    asArray(data.widgets).length > 0
      ? asArray(data.widgets).map(normalizeDashboardWidget)
      : dashboard.widgets ?? []

  const widgetData = asArray(data.widget_data ?? data.widgetData).map(normalizeDashboardWidgetData)
  const metrics = asArray(data.metrics).map(normalizeDashboardMetric)
  const datasets = asArray(data.datasets).map(normalizeDashboardDataset)

  return {
    dashboard,
    layout: data.layout ? normalizeDashboardLayout(data.layout) : dashboard.layout ?? null,
    widgets,
    widget_data: widgetData,
    datasets: datasets.length > 0 ? datasets : undefined,
    metrics: metrics.length > 0 ? metrics : undefined,
    filters: asArray(data.filters).map(normalizeDashboardFilter),
    actions: asArray(data.actions).map(normalizeDashboardAction),
    statistics: data.statistics ? normalizeDashboardStatistics(data.statistics) : null,
    permissions: (data.permissions as MetadataRecord | string[] | undefined) ?? undefined,
    runtime_context: asRecord(data.runtime_context ?? data.runtimeContext),
    metadata,
  }
}

export function normalizeDashboardBindingContext(
  raw: MetadataRecord | undefined,
  moduleKey: string,
  dashboardKey: string,
): DashboardBindingContext {
  const config = asRecord(raw)

  return {
    moduleKey,
    dashboardKey,
    source: asString(config.source, 'web') || 'web',
    page: asString(config.page),
    binding: asString(config.binding),
    auto_render: config.auto_render === true || config.autoRender === true,
    filters: asArray(config.filters).map(normalizeDashboardFilter),
    refresh_enabled: config.refresh_enabled !== false && config.refreshEnabled !== false,
    personalization_enabled:
      config.personalization_enabled !== false && config.personalizationEnabled !== false,
    empty_state_message: asString(config.empty_state_message ?? config.emptyStateMessage),
  }
}
