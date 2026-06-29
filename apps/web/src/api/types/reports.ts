import { asArray, asBoolean, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface ReportParameter {
  parameter_key: string
  label: string
  parameter_type: string
  required?: boolean
  default_value?: unknown
  options?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface ReportColumn {
  column_key: string
  label: string
  column_type: string
  sortable?: boolean
  filterable?: boolean
  visible?: boolean
  width?: number | null
  metadata?: MetadataRecord
}

export interface ReportMetric {
  metric_key: string
  label: string
  format?: string | null
  value?: unknown
  prefix?: string | null
  suffix?: string | null
  trend?: string | null
  comparison?: string | null
  status?: string | null
  metadata?: MetadataRecord
}

export interface ReportDataset {
  rows?: MetadataRecord[]
  total?: number
  metadata?: MetadataRecord
}

export interface ReportChart {
  chart_key: string
  label: string
  chart_type: string
  labels?: string[]
  datasets?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface ReportSection {
  section_key: string
  label: string
  section_type: string
  columns?: ReportColumn[]
  rows?: MetadataRecord[]
  metrics?: ReportMetric[]
  charts?: ReportChart[]
  content?: string | null
  sections?: ReportSection[]
  metadata?: MetadataRecord
}

export interface ReportAction {
  action_key: string
  label: string
  action_type: string
  permission?: string | null
  metadata?: MetadataRecord
}

export interface ReportFilter {
  filter_key: string
  label: string
  filter_type: string
  operator?: string
  value?: unknown
  metadata?: MetadataRecord
}

export interface ReportDefinition {
  public_id?: string
  module_key: string
  report_key: string
  name: string
  description?: string | null
  parameters?: ReportParameter[]
  sections?: ReportSection[]
  columns?: ReportColumn[]
  filters?: ReportFilter[]
  actions?: ReportAction[]
  metadata?: MetadataRecord
}

export interface ReportRenderPayload {
  report: ReportDefinition
  parameters?: ReportParameter[]
  sections: ReportSection[]
  datasets?: ReportDataset[]
  metrics?: ReportMetric[]
  charts?: ReportChart[]
  actions?: ReportAction[]
  filters?: ReportFilter[]
  columns?: ReportColumn[]
  metadata?: MetadataRecord
  permissions?: MetadataRecord | string[]
  runtime_context?: MetadataRecord
}

export interface ReportRunPayload {
  parameters?: Record<string, unknown>
  metadata?: MetadataRecord
}

export interface ReportRunResult {
  public_id?: string
  status?: string
  parameters?: Record<string, unknown>
  result?: MetadataRecord
  started_at?: string | null
  completed_at?: string | null
  duration_ms?: number | null
  metadata?: MetadataRecord
}

export interface ReportExportPayload {
  export_format: 'pdf' | 'xlsx' | 'csv' | 'json' | 'excel'
  parameters?: Record<string, unknown>
  metadata?: MetadataRecord
}

export interface ReportExportResult {
  public_id?: string
  export_format?: string
  status?: string
  file_reference?: MetadataRecord | null
  metadata?: MetadataRecord
}

export interface ReportBindingContext {
  moduleKey: string
  reportKey: string
  source?: string
  page?: string
  binding?: string
  auto_render?: boolean
  parameters?: ReportParameter[]
  filters?: ReportFilter[]
  export_enabled?: boolean
  run_enabled?: boolean
  empty_state_message?: string
}

function normalizeReportParameter(raw: unknown): ReportParameter {
  const data = asRecord(raw)
  return {
    parameter_key: asString(data.parameter_key ?? data.parameterKey ?? data.key ?? data.field_key),
    label: asString(data.label ?? data.name, 'Parameter'),
    parameter_type: asString(data.parameter_type ?? data.parameterType ?? data.type, 'text'),
    required: asBoolean(data.required),
    default_value: data.default_value ?? data.defaultValue ?? data.value,
    options: asArray(data.options).map((item) => asRecord(item)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportColumn(raw: unknown): ReportColumn {
  const data = asRecord(raw)
  return {
    column_key: asString(data.column_key ?? data.columnKey ?? data.key),
    label: asString(data.label ?? data.name, 'Column'),
    column_type: asString(data.column_type ?? data.columnType ?? data.type, 'text'),
    sortable: data.sortable !== false,
    filterable: data.filterable !== false,
    visible: data.visible !== false,
    width: typeof data.width === 'number' ? data.width : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportMetric(raw: unknown): ReportMetric {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    metric_key: asString(data.metric_key ?? data.metricKey ?? data.key),
    label: asString(data.label ?? data.name, 'Metric'),
    format: typeof data.format === 'string' ? data.format : null,
    value: data.value ?? metadata.value,
    prefix: typeof (data.prefix ?? metadata.prefix) === 'string' ? ((data.prefix ?? metadata.prefix) as string) : null,
    suffix: typeof (data.suffix ?? metadata.suffix) === 'string' ? ((data.suffix ?? metadata.suffix) as string) : null,
    trend: typeof (data.trend ?? metadata.trend) === 'string' ? ((data.trend ?? metadata.trend) as string) : null,
    comparison:
      typeof (data.comparison ?? metadata.comparison) === 'string'
        ? ((data.comparison ?? metadata.comparison) as string)
        : null,
    status: typeof (data.status ?? metadata.status) === 'string' ? ((data.status ?? metadata.status) as string) : null,
    metadata,
  }
}

export function normalizeReportChart(raw: unknown): ReportChart {
  const data = asRecord(raw)
  return {
    chart_key: asString(data.chart_key ?? data.chartKey ?? data.key),
    label: asString(data.label ?? data.name, 'Chart'),
    chart_type: asString(data.chart_type ?? data.chartType ?? data.type, 'line'),
    labels: asArray<string>(data.labels).map((label) => asString(label)),
    datasets: asArray(data.datasets).map((item) => asRecord(item)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportSection(raw: unknown): ReportSection {
  const data = asRecord(raw)
  return {
    section_key: asString(data.section_key ?? data.sectionKey ?? data.key ?? data.title),
    label: asString(data.label ?? data.title ?? data.name, 'Section'),
    section_type: asString(data.section_type ?? data.sectionType ?? data.type, 'custom'),
    columns: asArray(data.columns).map(normalizeReportColumn),
    rows: asArray(data.rows).map((row) => asRecord(row)),
    metrics: asArray(data.metrics).map(normalizeReportMetric),
    charts: asArray(data.charts).map(normalizeReportChart),
    content: typeof data.content === 'string' ? data.content : null,
    sections: asArray(data.sections).map(normalizeReportSection),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportAction(raw: unknown): ReportAction {
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

export function normalizeReportFilter(raw: unknown): ReportFilter {
  const data = asRecord(raw)
  return {
    filter_key: asString(data.filter_key ?? data.filterKey ?? data.field_key ?? data.key),
    label: asString(data.label ?? data.name, 'Filter'),
    filter_type: asString(data.filter_type ?? data.filterType ?? data.type, 'text'),
    operator: asString(data.operator, 'equals'),
    value: data.value,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportDataset(raw: unknown): ReportDataset {
  const data = asRecord(raw)
  return {
    rows: asArray(data.rows).map((row) => asRecord(row)),
    total: typeof data.total === 'number' ? data.total : undefined,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportDefinition(raw: unknown): ReportDefinition {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    report_key: asString(data.report_key ?? data.reportKey ?? data.key),
    name: asString(data.name ?? data.label, 'Report'),
    description: typeof data.description === 'string' ? data.description : null,
    parameters: asArray(data.parameters).map(normalizeReportParameter),
    sections: asArray(data.sections).map(normalizeReportSection),
    columns: asArray(data.columns).map(normalizeReportColumn),
    filters: asArray(data.filters).map(normalizeReportFilter),
    actions: asArray(data.actions).map(normalizeReportAction),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportRenderPayload(raw: unknown): ReportRenderPayload {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const layout = asRecord(data.layout)
  const reportSource = asRecord(data.report ?? data.definition)
  const report =
    Object.keys(reportSource).length > 0
      ? normalizeReportDefinition(reportSource)
      : normalizeReportDefinition({
          module_key: metadata.module_key ?? metadata.moduleKey,
          report_key: metadata.report_key ?? metadata.reportKey,
          name: metadata.name ?? metadata.label,
          description: metadata.description,
          public_id: metadata.public_id ?? metadata.publicId,
          parameters: data.parameters,
          sections: data.sections ?? layout.sections,
          columns: data.columns,
          filters: data.filters,
          actions: data.actions,
          metadata,
        })

  const sectionsRaw =
    asArray(data.sections).length > 0
      ? asArray(data.sections)
      : asArray(layout.sections).length > 0
        ? asArray(layout.sections)
        : report.sections ?? []

  const sections = sectionsRaw.map(normalizeReportSection)
  const metrics = asArray(data.metrics).map(normalizeReportMetric)
  const charts = asArray(data.charts).map(normalizeReportChart)
  const dataset = data.dataset ? normalizeReportDataset(data.dataset) : undefined

  return {
    report,
    parameters:
      asArray(data.parameters).length > 0
        ? asArray(data.parameters).map(normalizeReportParameter)
        : report.parameters ?? [],
    sections,
    datasets: dataset ? [dataset, ...asArray(data.datasets).map(normalizeReportDataset)] : asArray(data.datasets).map(normalizeReportDataset),
    metrics,
    charts,
    actions: asArray(data.actions).map(normalizeReportAction),
    filters: asArray(data.filters).map(normalizeReportFilter),
    columns: asArray(data.columns).map(normalizeReportColumn),
    metadata,
    permissions: (data.permissions as MetadataRecord | string[] | undefined) ?? undefined,
    runtime_context: asRecord(data.runtime_context ?? data.runtimeContext),
  }
}

export function normalizeReportRunResult(raw: unknown): ReportRunResult {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    status: asString(data.status),
    parameters: asRecord(data.parameters),
    result: asRecord(data.result),
    started_at: typeof data.started_at === 'string' ? data.started_at : null,
    completed_at: typeof data.completed_at === 'string' ? data.completed_at : null,
    duration_ms:
      typeof data.duration_ms === 'number'
        ? data.duration_ms
        : typeof data.durationMs === 'number'
          ? data.durationMs
          : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportExportResult(raw: unknown): ReportExportResult {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    export_format: asString(data.export_format ?? data.exportFormat),
    status: asString(data.status),
    file_reference: asRecord(data.file_reference ?? data.fileReference),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeReportBindingContext(
  raw: MetadataRecord | undefined,
  moduleKey: string,
  reportKey: string,
): ReportBindingContext {
  const config = asRecord(raw)
  return {
    moduleKey,
    reportKey,
    source: asString(config.source, 'web') || 'web',
    page: asString(config.page),
    binding: asString(config.binding),
    auto_render: config.auto_render === true || config.autoRender === true,
    parameters: asArray(config.parameters).map(normalizeReportParameter),
    filters: asArray(config.filters).map(normalizeReportFilter),
    export_enabled: config.export_enabled !== false && config.exportEnabled !== false,
    run_enabled: config.run_enabled !== false && config.runEnabled !== false,
    empty_state_message: asString(config.empty_state_message ?? config.emptyStateMessage),
  }
}

function mapExportFormat(format: ReportExportPayload['export_format']): string {
  return format === 'xlsx' ? 'excel' : format
}

export function buildReportExportRequest(payload: ReportExportPayload): Record<string, unknown> {
  return {
    export_format: mapExportFormat(payload.export_format),
    parameters: payload.parameters ?? {},
    metadata: payload.metadata,
  }
}
