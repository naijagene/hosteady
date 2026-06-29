import type {
  TableAction,
  TableColumn,
  TableDefinition,
  TableFilter,
  TableSort,
  TableView,
} from '@/api/types/tables'

export interface NormalizedTableColumn extends TableColumn {
  visibleInView: boolean
}

export interface NormalizedTableDefinition {
  definition: TableDefinition
  columns: NormalizedTableColumn[]
  columnMap: Map<string, NormalizedTableColumn>
  actions: TableAction[]
  views: TableView[]
  defaultSort: TableSort | null
  createForm?: { module_key: string; form_key: string } | null
  editForm?: { module_key: string; form_key: string } | null
}

export interface TableQueryState {
  page: number
  perPage: number
  search: string
  filters: TableFilter[]
  sorts: TableSort[]
  selectedView: string | null
}

export type TableRowSelection = Set<string>
