import type { ReportAction } from '@/api/types/reports'

export function isSupportedReportActionType(actionType: string): boolean {
  return ['run', 'refresh', 'export', 'schedule', 'email', 'open_dashboard', 'custom'].includes(
    actionType.toLowerCase(),
  )
}

export function resolveReportToolbarActions(actions: ReportAction[]): ReportAction[] {
  return actions.filter((action) => action.action_type.toLowerCase() !== 'row')
}

export function getDefaultReportActions(options?: {
  runEnabled?: boolean
  exportEnabled?: boolean
}): ReportAction[] {
  const actions: ReportAction[] = []

  if (options?.runEnabled !== false) {
    actions.push({ action_key: 'run', label: 'Run', action_type: 'run' })
  }

  actions.push({ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' })

  if (options?.exportEnabled !== false) {
    actions.push({ action_key: 'export', label: 'Export', action_type: 'export' })
  }

  return actions
}

export function getReportActionPlaceholder(action: ReportAction): string {
  switch (action.action_type.toLowerCase()) {
    case 'schedule':
      return 'Schedule report is not implemented yet.'
    case 'email':
      return 'Email report is not implemented yet.'
    case 'open_dashboard':
      return 'Open dashboard is not implemented yet.'
    case 'custom':
      return `${action.label} is not supported yet.`
    default:
      return `${action.label} is not supported yet.`
  }
}
