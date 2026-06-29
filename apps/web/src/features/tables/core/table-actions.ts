import type { TableAction } from '@/api/types/tables'

export function resolveTableActions(actions: TableAction[]): {
  toolbar: TableAction[]
  row: TableAction[]
} {
  const toolbar: TableAction[] = []
  const row: TableAction[] = []

  actions.forEach((action) => {
    const type = action.action_type.toLowerCase()

    if (type === 'row' || type === 'view' || type === 'edit' || type === 'delete') {
      row.push(action)
      return
    }

    toolbar.push(action)
  })

  return { toolbar, row }
}

export function isSupportedActionType(actionType: string): boolean {
  return ['create', 'view', 'edit', 'delete', 'export', 'refresh', 'custom'].includes(
    actionType.toLowerCase(),
  )
}

export function getDefaultToolbarActions(options: {
  createEnabled?: boolean
  exportEnabled?: boolean
}): TableAction[] {
  const actions: TableAction[] = []

  if (options.createEnabled !== false) {
    actions.push({
      action_key: 'create',
      label: 'Create',
      action_type: 'create',
    })
  }

  actions.push({
    action_key: 'refresh',
    label: 'Refresh',
    action_type: 'refresh',
  })

  if (options.exportEnabled !== false) {
    actions.push({
      action_key: 'export',
      label: 'Export',
      action_type: 'export',
    })
  }

  return actions
}
