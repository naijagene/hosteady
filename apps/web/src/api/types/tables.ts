import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface TableColumn {
  column_key: string
  label: string
  data_type?: string
  sortable?: boolean
  filterable?: boolean
  metadata?: MetadataRecord
}

export interface TableFilter {
  filter_key: string
  label: string
  operator?: string
  value?: unknown
}

export interface TableSort {
  column_key: string
  direction: 'asc' | 'desc'
}

export interface TableDefinition {
  public_id?: string
  module_key: string
  table_key: string
  name: string
  description?: string | null
  columns?: TableColumn[]
  metadata?: MetadataRecord
}

export interface TableQueryPayload {
  filters?: TableFilter[]
  sort?: TableSort[]
  page?: number
  per_page?: number
  metadata?: MetadataRecord
}

export interface TableQueryResult {
  columns?: TableColumn[]
  rows?: MetadataRecord[]
  total?: number
  page?: number
  per_page?: number
  metadata?: MetadataRecord
}

export function normalizeTableDefinition(raw: unknown): TableDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    table_key: asString(data.table_key ?? data.tableKey),
    name: asString(data.name, 'Table'),
    description:
      typeof data.description === 'string' ? data.description : null,
    columns: asArray(data.columns).map((column) => {
      const item = asRecord(column)
      return {
        column_key: asString(item.column_key ?? item.columnKey),
        label: asString(item.label, 'Column'),
        data_type: asString(item.data_type ?? item.dataType),
        sortable: item.sortable === true,
        filterable: item.filterable === true,
        metadata: asRecord(item.metadata),
      }
    }),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeTableQueryResult(raw: unknown): TableQueryResult {
  const data = asRecord(raw)

  return {
    columns: asArray(data.columns).map((column) => {
      const item = asRecord(column)
      return {
        column_key: asString(item.column_key ?? item.columnKey),
        label: asString(item.label, 'Column'),
        data_type: asString(item.data_type ?? item.dataType),
        metadata: asRecord(item.metadata),
      }
    }),
    rows: asArray<MetadataRecord>(data.rows ?? data.data),
    total: typeof data.total === 'number' ? data.total : undefined,
    page: typeof data.page === 'number' ? data.page : undefined,
    per_page:
      typeof data.per_page === 'number'
        ? data.per_page
        : typeof data.perPage === 'number'
          ? data.perPage
          : undefined,
    metadata: asRecord(data.metadata),
  }
}
