import { describe, expect, it } from 'vitest'
import type { TableDefinition } from '@/api/types/tables'
import {
  extractTablePagination,
  normalizeTableBindingContext,
  normalizeTableColumn,
  normalizeTableDefinition,
  normalizeTableFilter,
  normalizeTableQueryResult,
  normalizeTableRow,
  normalizeTableSort,
} from '@/api/types/tables'
import {
  getDefaultToolbarActions,
  isSupportedActionType,
  resolveTableActions,
} from '@/features/tables/core/table-actions'
import { toggleColumnVisibility } from '@/features/tables/core/table-columns'
import {
  countActiveFilters,
  isFilterActive,
  normalizeFilterOperator,
  removeFilter,
  serializeFilter,
  serializeFilters,
  upsertFilter,
} from '@/features/tables/core/table-filters'
import { toTableQueryError } from '@/features/tables/core/table-errors'
import {
  getVisibleColumns,
  normalizeTableDefinitionModel,
} from '@/features/tables/core/table-normalizer'
import {
  canCreate,
  canDelete,
  canEdit,
  filterActionsByPermission,
  hasPermission,
} from '@/features/tables/core/table-permissions'
import {
  buildTableQueryPayload,
  createInitialQueryState,
  tableQueryKey,
} from '@/features/tables/core/table-query'
import {
  clearRowSelection,
  getRowSelectionKey,
  isAllVisibleSelected,
  toggleAllVisibleRows,
  toggleRowSelection,
} from '@/features/tables/core/table-selection'
import {
  ariaSortValue,
  getColumnSortDirection,
  serializeSorts,
  toggleColumnSort,
} from '@/features/tables/core/table-sorts'
import { ApiError } from '@/api/errors'

const definition: TableDefinition = {
  module_key: 'platform',
  table_key: 'users',
  name: 'Users',
  columns: [
    { column_key: 'name', label: 'Name', column_type: 'text', sortable: true },
    { column_key: 'status', label: 'Status', column_type: 'status' },
  ],
  actions: [
    { action_key: 'create', label: 'Create', action_type: 'create' },
    { action_key: 'edit', label: 'Edit', action_type: 'edit' },
  ],
  create_form: { module_key: 'platform', form_key: 'user-create' },
  edit_form: { module_key: 'platform', form_key: 'user-edit' },
}

describe('table API normalization', () => {
  it('normalizes camelCase column keys', () => {
    const column = normalizeTableColumn({ key: 'email', type: 'email', label: 'Email' })
    expect(column.column_key).toBe('email')
    expect(column.column_type).toBe('email')
  })

  it('normalizes table definition snake and camel keys', () => {
    const normalized = normalizeTableDefinition({
      moduleKey: 'platform',
      tableKey: 'users',
      name: 'Users',
      defaultSort: { columnKey: 'name', direction: 'desc' },
    })
    expect(normalized.module_key).toBe('platform')
    expect(normalized.default_sort?.column_key).toBe('name')
    expect(normalized.default_sort?.direction).toBe('desc')
  })

  it('normalizes legacy flat rows', () => {
    const row = normalizeTableRow({ public_id: '1', name: 'Ada', email: 'ada@test.com' })
    expect(row.public_id).toBe('1')
    expect(row.values.name).toBe('Ada')
  })

  it('normalizes query result pagination aliases', () => {
    const result = normalizeTableQueryResult({
      rows: [{ values: { name: 'Ada' } }],
      total: 50,
      page: 2,
      perPage: 25,
      totalPages: 2,
    })
    expect(result.rows).toHaveLength(1)
    expect(result.last_page).toBe(2)
  })

  it('normalizes binding context flags', () => {
    const binding = normalizeTableBindingContext(
      {
        autoQuery: true,
        perPage: 50,
        createEnabled: false,
        refreshOnFormSuccess: true,
      },
      'platform',
      'users',
    )
    expect(binding.auto_query).toBe(true)
    expect(binding.per_page).toBe(50)
    expect(binding.create_enabled).toBe(false)
    expect(binding.refresh_on_form_success).toBe(true)
  })

  it('extracts defensive pagination metadata', () => {
    expect(extractTablePagination({ total: 0, page: 1, per_page: 25 })).toEqual({
      page: 1,
      per_page: 25,
      total: 0,
      last_page: 1,
    })
  })
})

describe('table normalizer model', () => {
  it('builds normalized model with form references', () => {
    const model = normalizeTableDefinitionModel(definition)
    expect(model.createForm?.form_key).toBe('user-create')
    expect(model.editForm?.form_key).toBe('user-edit')
  })

  it('filters columns by view and hidden keys', () => {
    const model = normalizeTableDefinitionModel({
      ...definition,
      views: [{ view_key: 'compact', label: 'Compact', columns: ['name'] }],
    })
    const hidden = toggleColumnVisibility(new Set(), 'name')
    const visible = getVisibleColumns(model, hidden, 'compact')
    expect(visible).toHaveLength(0)
  })
})

describe('table query payload', () => {
  it('creates initial query state with defaults', () => {
    expect(createInitialQueryState({ perPage: 10 })).toEqual({
      page: 1,
      perPage: 10,
      search: '',
      filters: [],
      sorts: [],
      selectedView: null,
    })
  })

  it('builds query payload with metadata', () => {
    const payload = buildTableQueryPayload(
      {
        page: 2,
        perPage: 25,
        search: ' ada ',
        filters: [{ column_key: 'name', operator: 'contains', value: 'ada' }],
        sorts: [{ column_key: 'name', direction: 'asc' }],
        selectedView: 'all',
      },
      {
        binding: { moduleKey: 'platform', tableKey: 'users', source: 'web', page: '/app' },
        visibleColumnKeys: ['name'],
      },
    )
    expect(payload.search).toBe('ada')
    expect(payload.sorts).toHaveLength(1)
    expect(payload.columns).toEqual(['name'])
    expect(payload.metadata?.source).toBe('web')
  })

  it('builds stable query keys', () => {
    const state = createInitialQueryState()
    expect(tableQueryKey('platform', 'users', state, ['name'])[0]).toBe('table-query')
  })
})

describe('table filters', () => {
  it('normalizes filter operators', () => {
    expect(normalizeFilterOperator('gt')).toBe('greater_than')
    expect(normalizeFilterOperator('not_empty')).toBe('is_not_empty')
  })

  it('serializes filters for backend', () => {
    const filter = serializeFilter({ column_key: 'name', operator: 'eq', value: 'Ada' })
    expect(filter.operator).toBe('equals')
  })

  it('upserts and removes filters', () => {
    const first = upsertFilter([], { column_key: 'name', operator: 'contains', value: 'a' })
    const second = upsertFilter(first, { column_key: 'name', operator: 'equals', value: 'b' })
    expect(second).toHaveLength(1)
    expect(removeFilter(second, 'name')).toHaveLength(0)
  })

  it('counts active filters', () => {
    expect(
      countActiveFilters([
        { column_key: 'name', operator: 'is_empty' },
        { column_key: 'status', operator: 'equals', value: '' },
      ]),
    ).toBe(1)
  })

  it('serializes filter arrays', () => {
    expect(
      serializeFilters([{ column_key: 'name', operator: 'contains', value: 'x' }])[0].operator,
    ).toBe('contains')
  })

  it('detects active filters', () => {
    expect(isFilterActive({ column_key: 'name', operator: 'is_not_empty' })).toBe(true)
  })
})

describe('table sorts', () => {
  it('toggles sort asc desc none', () => {
    expect(toggleColumnSort([], 'name')).toEqual([{ column_key: 'name', direction: 'asc' }])
    expect(toggleColumnSort([{ column_key: 'name', direction: 'asc' }], 'name')).toEqual([
      { column_key: 'name', direction: 'desc' },
    ])
    expect(toggleColumnSort([{ column_key: 'name', direction: 'desc' }], 'name')).toEqual([])
  })

  it('returns aria sort values', () => {
    expect(ariaSortValue('asc')).toBe('ascending')
    expect(ariaSortValue(null)).toBe('none')
  })

  it('reads column sort direction', () => {
    expect(
      getColumnSortDirection([{ column_key: 'name', direction: 'desc' }], 'name'),
    ).toBe('desc')
  })

  it('serializes sorts', () => {
    expect(
      serializeSorts([{ column_key: 'name', direction: 'desc' }])[0].direction,
    ).toBe('desc')
  })
})

describe('table actions and permissions', () => {
  it('separates toolbar and row actions', () => {
    const resolved = resolveTableActions([
      { action_key: 'create', label: 'Create', action_type: 'create' },
      { action_key: 'edit', label: 'Edit', action_type: 'edit' },
    ])
    expect(resolved.toolbar).toHaveLength(1)
    expect(resolved.row).toHaveLength(1)
  })

  it('adds default toolbar actions', () => {
    const actions = getDefaultToolbarActions({ createEnabled: true, exportEnabled: true })
    expect(actions.map((action) => action.action_type)).toEqual(['create', 'refresh', 'export'])
  })

  it('checks supported action types', () => {
    expect(isSupportedActionType('refresh')).toBe(true)
    expect(isSupportedActionType('unknown')).toBe(false)
  })

  it('filters actions by permission', () => {
    const actions = filterActionsByPermission(
      [
        { action_key: 'create', label: 'Create', action_type: 'create', permission: 'users.create' },
        { action_key: 'refresh', label: 'Refresh', action_type: 'refresh' },
      ],
      ['users.create'],
    )
    expect(actions).toHaveLength(2)
  })

  it('evaluates create edit delete permissions', () => {
    expect(canCreate({ permissions: ['users.create'], createEnabled: true })).toBe(true)
    expect(canEdit({ permissions: [], editEnabled: false })).toBe(false)
    expect(canDelete({ permissions: ['users.delete'], deleteEnabled: true })).toBe(true)
    expect(hasPermission(['a'], 'b')).toBe(false)
  })
})

describe('table selection', () => {
  it('uses public_id as selection key', () => {
    expect(getRowSelectionKey({ public_id: 'abc', values: {} }, 0)).toBe('abc')
  })

  it('toggles row selection', () => {
    expect(toggleRowSelection(new Set(), 'abc').has('abc')).toBe(true)
    expect(toggleRowSelection(new Set(['abc']), 'abc').has('abc')).toBe(false)
  })

  it('selects all visible rows', () => {
    const next = toggleAllVisibleRows(new Set(), ['a', 'b'], true)
    expect(isAllVisibleSelected(next, ['a', 'b'])).toBe(true)
  })

  it('clears selection', () => {
    expect(clearRowSelection().size).toBe(0)
  })
})

describe('table errors', () => {
  it('maps ApiError to table query error', () => {
    const error = toTableQueryError(
      new ApiError('Failed', {
        status: 422,
        body: { message: 'Failed', errors: { name: ['Invalid'] } },
      }),
    )
    expect(error.message).toBe('Failed')
    expect(error.status).toBe(422)
  })

  it('maps unknown errors safely', () => {
    expect(toTableQueryError('boom').message).toBe('Unable to load table data.')
  })
})

describe('table column visibility', () => {
  it('toggles hidden columns', () => {
    const hidden = toggleColumnVisibility(new Set(), 'name')
    expect(hidden.has('name')).toBe(true)
    expect(toggleColumnVisibility(hidden, 'name').has('name')).toBe(false)
  })
})

describe('table filter and sort normalization helpers', () => {
  it('normalizes filter snake_case keys', () => {
    expect(normalizeTableFilter({ columnKey: 'name', operator: 'contains', value: 'x' }).column_key).toBe(
      'name',
    )
  })

  it('normalizes sort direction', () => {
    expect(normalizeTableSort({ columnKey: 'name', direction: 'DESC' }).direction).toBe('desc')
  })
})
