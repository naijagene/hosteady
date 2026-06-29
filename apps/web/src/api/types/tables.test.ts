import { describe, expect, it } from 'vitest'
import {
  normalizeTableAction,
  normalizeTableDefinition,
  normalizeTableQueryResult,
  normalizeTableView,
} from '@/api/types/tables'

describe('tables API types', () => {
  it('normalizes table actions with permission aliases', () => {
    const action = normalizeTableAction({
      actionKey: 'edit',
      label: 'Edit',
      actionType: 'edit',
      required_permission: 'users.edit',
    })
    expect(action.action_key).toBe('edit')
    expect(action.permission).toBe('users.edit')
  })

  it('normalizes views and columns', () => {
    const view = normalizeTableView({
      viewKey: 'compact',
      label: 'Compact',
      columns: ['name'],
    })
    expect(view.view_key).toBe('compact')
    expect(view.columns).toEqual(['name'])
  })

  it('normalizes nested create and edit forms', () => {
    const definition = normalizeTableDefinition({
      module_key: 'platform',
      table_key: 'users',
      name: 'Users',
      metadata: {
        create_form: { module_key: 'platform', form_key: 'create' },
      },
      edit_form: { module_key: 'platform', form_key: 'edit' },
    })
    expect(definition.create_form?.module_key).toBe('platform')
    expect(definition.edit_form?.form_key).toBe('edit')
  })

  it('normalizes query result applied filters and sorts', () => {
    const result = normalizeTableQueryResult({
      appliedFilters: [{ columnKey: 'name', operator: 'contains', value: 'a' }],
      appliedSorts: [{ columnKey: 'name', direction: 'asc' }],
      rows: [],
    })
    expect(result.applied_filters?.[0].column_key).toBe('name')
    expect(result.applied_sorts?.[0].direction).toBe('asc')
  })
})
