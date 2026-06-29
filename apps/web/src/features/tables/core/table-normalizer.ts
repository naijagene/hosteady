import { asRecord, asString } from '@/api/types/metadata-common'
import type { TableDefinition } from '@/api/types/tables'
import type { NormalizedTableColumn, NormalizedTableDefinition } from '../types'

function extractFormReference(
  source: Record<string, unknown> | null | undefined,
): { module_key: string; form_key: string } | null {
  if (!source) {
    return null
  }

  const moduleKey = asString(source.module_key ?? source.moduleKey)
  const formKey = asString(source.form_key ?? source.formKey)

  if (!moduleKey || !formKey) {
    return null
  }

  return { module_key: moduleKey, form_key: formKey }
}

export function normalizeTableDefinitionModel(
  definition: TableDefinition,
): NormalizedTableDefinition {
  const columns: NormalizedTableColumn[] = (definition.columns ?? []).map(
    (column) => ({
      ...column,
      visibleInView: column.visible !== false,
    }),
  )

  return {
    definition,
    columns,
    columnMap: new Map(columns.map((column) => [column.column_key, column])),
    actions: definition.actions ?? [],
    views: definition.views ?? [],
    defaultSort: definition.default_sort ?? null,
    createForm: extractFormReference(
      asRecord(definition.create_form ?? definition.metadata?.create_form),
    ),
    editForm: extractFormReference(
      asRecord(definition.edit_form ?? definition.metadata?.edit_form),
    ),
  }
}

export function getVisibleColumns(
  model: NormalizedTableDefinition,
  hiddenColumnKeys: Set<string>,
  selectedView: string | null,
): NormalizedTableColumn[] {
  const view = model.views.find((item) => item.view_key === selectedView)
  const viewColumns = view?.columns ?? []

  return model.columns.filter((column) => {
    if (hiddenColumnKeys.has(column.column_key)) {
      return false
    }

    if (viewColumns.length > 0) {
      return viewColumns.includes(column.column_key)
    }

    return column.visibleInView
  })
}
