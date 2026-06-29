import {
  asArray,
  asBoolean,
  asRecord,
  asString,
  type MetadataRecord,
} from './metadata-common'

export interface TableColumnOption {
  value: string
  label: string
  metadata?: MetadataRecord
}

export interface TableColumn {
  column_key: string
  label: string
  column_type: string
  data_type?: string
  sortable?: boolean
  filterable?: boolean
  searchable?: boolean
  visible?: boolean
  width?: number | null
  options?: TableColumnOption[]
  metadata?: MetadataRecord
}

export interface TableFilter {
  column_key: string
  operator: string
  value?: unknown
  metadata?: MetadataRecord
}

export interface TableSort {
  column_key: string
  direction: 'asc' | 'desc'
}

export interface TableAction {
  action_key: string
  label: string
  action_type: string
  handler?: string | null
  confirm_message?: string | null
  permission?: string | null
  metadata?: MetadataRecord
}

export interface TableView {
  view_key: string
  label: string
  columns?: string[]
  metadata?: MetadataRecord
}

export interface TableRow {
  public_id?: string | null
  values: MetadataRecord
  metadata?: MetadataRecord
}

export interface TablePagination {
  page: number
  per_page: number
  total: number
  last_page: number
}

export interface TableDefinition {
  public_id?: string
  module_key: string
  table_key: string
  name: string
  description?: string | null
  entity_key?: string | null
  columns?: TableColumn[]
  filters?: TableFilter[]
  sorts?: TableSort[]
  default_sort?: TableSort | null
  pagination?: MetadataRecord
  actions?: TableAction[]
  views?: TableView[]
  create_form?: MetadataRecord | null
  edit_form?: MetadataRecord | null
  metadata?: MetadataRecord
}

export interface TableQueryPayload {
  page?: number
  per_page?: number
  search?: string | null
  filters?: TableFilter[]
  sorts?: TableSort[]
  selected_view?: string | null
  columns?: string[]
  metadata?: MetadataRecord
}

export interface TableQueryResult {
  module_key?: string
  table_key?: string
  columns?: TableColumn[]
  rows?: TableRow[]
  total?: number
  page?: number
  per_page?: number
  last_page?: number
  total_pages?: number
  applied_filters?: TableFilter[]
  applied_sorts?: TableSort[]
  metadata?: MetadataRecord
}

export interface TableQueryError {
  message: string
  status?: number | null
  field_errors?: Record<string, string[]>
}

export interface TableBindingContext {
  moduleKey: string
  tableKey: string
  source?: string
  page?: string
  binding?: string
  auto_query?: boolean
  query_enabled?: boolean
  per_page?: number
  default_filters?: TableFilter[]
  default_sorts?: TableSort[]
  create_enabled?: boolean
  edit_enabled?: boolean
  delete_enabled?: boolean
  export_enabled?: boolean
  refresh_on_form_success?: boolean
}

function normalizeTableColumnOption(raw: unknown): TableColumnOption {
  const data = asRecord(raw)
  return {
    value: asString(data.value),
    label: asString(data.label, 'Option'),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableColumn(raw: unknown): TableColumn {
  const data = asRecord(raw)

  return {
    column_key: asString(data.column_key ?? data.columnKey ?? data.key),
    label: asString(data.label ?? data.name, 'Column'),
    column_type: asString(
      data.column_type ?? data.columnType ?? data.type ?? data.data_type ?? data.dataType,
      'text',
    ),
    data_type: asString(data.data_type ?? data.dataType ?? data.type),
    sortable: data.sortable !== false,
    filterable: data.filterable !== false,
    searchable: asBoolean(data.searchable),
    visible: data.visible !== false,
    width:
      typeof data.width === 'number'
        ? data.width
        : typeof data.width === 'string'
          ? Number(data.width)
          : null,
    options: asArray(data.options).map(normalizeTableColumnOption),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableFilter(raw: unknown): TableFilter {
  const data = asRecord(raw)

  return {
    column_key: asString(data.column_key ?? data.columnKey ?? data.key ?? data.filter_key),
    operator: asString(data.operator, 'equals'),
    value: data.value,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableSort(raw: unknown): TableSort {
  const data = asRecord(raw)
  const direction = asString(data.direction, 'asc').toLowerCase()

  return {
    column_key: asString(data.column_key ?? data.columnKey ?? data.key),
    direction: direction === 'desc' ? 'desc' : 'asc',
  }
}

export function normalizeTableAction(raw: unknown): TableAction {
  const data = asRecord(raw)

  return {
    action_key: asString(data.action_key ?? data.actionKey ?? data.key),
    label: asString(data.label, 'Action'),
    action_type: asString(data.action_type ?? data.actionType ?? data.type, 'custom'),
    handler: typeof data.handler === 'string' ? data.handler : null,
    confirm_message:
      typeof (data.confirm_message ?? data.confirmMessage) === 'string'
        ? ((data.confirm_message ?? data.confirmMessage) as string)
        : null,
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableView(raw: unknown): TableView {
  const data = asRecord(raw)

  return {
    view_key: asString(data.view_key ?? data.viewKey ?? data.key),
    label: asString(data.label, 'View'),
    columns: asArray<string>(data.columns).map((column) => asString(column)),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableRow(raw: unknown): TableRow {
  const data = asRecord(raw)
  const values = asRecord(data.values)

  if (Object.keys(values).length === 0) {
    const rowValues: MetadataRecord = { ...data }
    delete rowValues.public_id
    delete rowValues.publicId
    delete rowValues.metadata
    delete rowValues.values

    return {
      public_id: asString(data.public_id ?? data.publicId) || null,
      values: rowValues,
      metadata: asRecord(data.metadata),
    }
  }

  return {
    public_id: asString(data.public_id ?? data.publicId) || null,
    values,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableDefinition(raw: unknown): TableDefinition {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    table_key: asString(data.table_key ?? data.tableKey ?? data.key),
    name: asString(data.name ?? data.label, 'Table'),
    description:
      typeof data.description === 'string' ? data.description : null,
    entity_key:
      typeof (data.entity_key ?? data.entityKey) === 'string'
        ? ((data.entity_key ?? data.entityKey) as string)
        : null,
    columns: asArray(data.columns).map(normalizeTableColumn),
    filters: asArray(data.filters).map(normalizeTableFilter),
    sorts: asArray(data.sorts).map(normalizeTableSort),
    default_sort:
      data.default_sort || data.defaultSort
        ? normalizeTableSort(data.default_sort ?? data.defaultSort)
        : null,
    pagination: asRecord(data.pagination),
    actions: asArray(data.actions).map(normalizeTableAction),
    views: asArray(data.views).map(normalizeTableView),
    create_form: asRecord(data.create_form ?? data.createForm ?? metadata.create_form ?? metadata.createForm),
    edit_form: asRecord(data.edit_form ?? data.editForm ?? metadata.edit_form ?? metadata.editForm),
    metadata,
  }
}

export function normalizeTableQueryResult(raw: unknown): TableQueryResult {
  const data = asRecord(raw)
  const totalPages =
    typeof data.total_pages === 'number'
      ? data.total_pages
      : typeof data.totalPages === 'number'
        ? data.totalPages
        : typeof data.last_page === 'number'
          ? data.last_page
          : typeof data.lastPage === 'number'
            ? data.lastPage
            : undefined

  return {
    module_key: asString(data.module_key ?? data.moduleKey),
    table_key: asString(data.table_key ?? data.tableKey),
    columns: asArray(data.columns).map(normalizeTableColumn),
    rows: asArray(data.rows ?? data.data).map(normalizeTableRow),
    total: typeof data.total === 'number' ? data.total : undefined,
    page: typeof data.page === 'number' ? data.page : undefined,
    per_page:
      typeof data.per_page === 'number'
        ? data.per_page
        : typeof data.perPage === 'number'
          ? data.perPage
          : undefined,
    last_page: totalPages,
    total_pages: totalPages,
    applied_filters: asArray(data.applied_filters ?? data.appliedFilters).map(
      normalizeTableFilter,
    ),
    applied_sorts: asArray(data.applied_sorts ?? data.appliedSorts).map(
      normalizeTableSort,
    ),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableBindingContext(
  raw: MetadataRecord | undefined,
  moduleKey: string,
  tableKey: string,
): TableBindingContext {
  const config = asRecord(raw)

  return {
    moduleKey,
    tableKey,
    source: asString(config.source, 'web') || 'web',
    page: asString(config.page),
    binding: asString(config.binding),
    auto_query: config.auto_query === true || config.autoQuery === true,
    query_enabled: config.query_enabled !== false && config.queryEnabled !== false,
    per_page:
      typeof (config.per_page ?? config.perPage) === 'number'
        ? ((config.per_page ?? config.perPage) as number)
        : undefined,
    default_filters: asArray(config.default_filters ?? config.defaultFilters).map(
      normalizeTableFilter,
    ),
    default_sorts: asArray(config.default_sorts ?? config.defaultSorts).map(
      normalizeTableSort,
    ),
    create_enabled: config.create_enabled !== false && config.createEnabled !== false,
    edit_enabled: config.edit_enabled !== false && config.editEnabled !== false,
    delete_enabled: config.delete_enabled !== false && config.deleteEnabled !== false,
    export_enabled: config.export_enabled !== false && config.exportEnabled !== false,
    refresh_on_form_success:
      config.refresh_on_form_success === true ||
      config.refreshOnFormSuccess === true,
  }
}

export function extractTablePagination(result: TableQueryResult | null | undefined): TablePagination {
  const page = result?.page ?? 1
  const perPage = result?.per_page ?? 25
  const total = result?.total ?? 0
  const lastPage =
    result?.last_page ??
    result?.total_pages ??
    (perPage > 0 ? Math.max(1, Math.ceil(total / perPage)) : 1)

  return {
    page,
    per_page: perPage,
    total,
    last_page: lastPage,
  }
}
