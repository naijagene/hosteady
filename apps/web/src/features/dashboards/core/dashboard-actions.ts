import type { DashboardAction } from '@/api/types/dashboards'

export function isSupportedDashboardActionType(actionType: string): boolean {
  return ['refresh', 'export', 'open_report', 'start_workflow', 'custom'].includes(
    actionType.toLowerCase(),
  )
}

export function resolveDashboardToolbarActions(actions: DashboardAction[]): DashboardAction[] {
  return actions.filter((action) => {
    const type = action.action_type.toLowerCase()
    return type !== 'row' && type !== 'widget'
  })
}

export function getDefaultDashboardActions(refreshEnabled = true): DashboardAction[] {
  const actions: DashboardAction[] = []

  if (refreshEnabled) {
    actions.push({
      action_key: 'refresh',
      label: 'Refresh',
      action_type: 'refresh',
    })
  }

  actions.push({
    action_key: 'export',
    label: 'Export',
    action_type: 'export',
  })

  return actions
}

export function getActionPlaceholderMessage(action: DashboardAction): string {
  switch (action.action_type.toLowerCase()) {
    case 'export':
      return 'Export is not implemented yet.'
    case 'open_report':
      return 'Open report is not implemented yet.'
    case 'start_workflow':
      return 'Start workflow is not implemented yet.'
    case 'custom':
      return `${action.label} is not supported yet.`
    default:
      return `${action.label} is not supported yet.`
  }
}
