import { useMemo } from 'react'
import type { TableBindingContext, TableDefinition } from '@/api/types/tables'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { filterActionsByPermission } from '../core/table-permissions'
import { getDefaultToolbarActions, resolveTableActions } from '../core/table-actions'
import { normalizeTableDefinitionModel } from '../core/table-normalizer'
import { createInitialQueryState } from '../core/table-query'
import type { TableQueryState } from '../types'

export function useDynamicTable(options: {
  definition: TableDefinition
  binding?: TableBindingContext
}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(
    () => runtime?.permissions ?? [],
    [runtime?.permissions],
  )

  const model = useMemo(
    () => normalizeTableDefinitionModel(options.definition),
    [options.definition],
  )

  const initialQueryState = useMemo<TableQueryState>(
    () =>
      createInitialQueryState({
        perPage: options.binding?.per_page ?? 25,
        defaultFilters: options.binding?.default_filters,
        defaultSorts:
          options.binding?.default_sorts ??
          (model.defaultSort ? [model.defaultSort] : []),
      }),
    [options.binding, model.defaultSort],
  )

  const toolbarActions = useMemo(() => {
    const defaults = getDefaultToolbarActions({
      createEnabled: options.binding?.create_enabled,
      exportEnabled: options.binding?.export_enabled,
    })
    const resolved = resolveTableActions([
      ...defaults,
      ...model.actions.filter((action) =>
        ['create', 'export', 'refresh', 'custom'].includes(
          action.action_type.toLowerCase(),
        ),
      ),
    ])
    const seen = new Set<string>()

    return filterActionsByPermission(
      resolved.toolbar.filter((action) => {
        if (seen.has(action.action_key)) {
          return false
        }

        seen.add(action.action_key)
        return true
      }),
      permissions,
    )
  }, [model.actions, options.binding, permissions])

  const rowActions = useMemo(() => {
    const resolved = resolveTableActions(model.actions)
    return filterActionsByPermission(resolved.row, permissions)
  }, [model.actions, permissions])

  return {
    model,
    permissions,
    initialQueryState,
    toolbarActions,
    rowActions,
  }
}
