import type { CommandAction, SearchResult } from '@/api/types/search'

export function resolveSearchResultRoute(result: SearchResult): string | null {
  if (result.route) {
    return result.route
  }

  const metadata = result.metadata ?? {}
  const moduleKey = metadata.module_key ?? metadata.moduleKey
  const pageKey = metadata.page_key ?? metadata.pageKey

  switch (result.type) {
    case 'page':
      return typeof moduleKey === 'string' && typeof pageKey === 'string'
        ? `/app/${moduleKey}/${pageKey}`
        : null
    case 'document':
      return typeof metadata.document_public_id === 'string'
        ? `/documents/${metadata.document_public_id}`
        : null
    case 'report':
      return typeof moduleKey === 'string' && typeof metadata.report_key === 'string'
        ? `/reports/${moduleKey}/${metadata.report_key}`
        : null
    case 'dashboard':
      return typeof moduleKey === 'string' && typeof metadata.dashboard_key === 'string'
        ? `/dashboards/${moduleKey}/${metadata.dashboard_key}`
        : null
    case 'workflow':
    case 'task':
      return typeof metadata.task_public_id === 'string'
        ? `/workflows/tasks/${metadata.task_public_id}`
        : '/workflows'
    case 'approval':
      return typeof metadata.approval_public_id === 'string'
        ? `/workflows/approvals/${metadata.approval_public_id}`
        : '/workflows'
    case 'notification':
      return typeof metadata.public_id === 'string'
        ? `/notifications/${metadata.public_id}`
        : '/notifications'
    default:
      return null
  }
}

export function resolveSearchAction(result: SearchResult): CommandAction {
  if (result.action) {
    return result.action
  }

  const route = resolveSearchResultRoute(result)
  if (route) {
    return { action_type: 'navigate', route }
  }

  if (result.type === 'command' && result.metadata?.command_key) {
    return {
      action_type: 'execute_command',
      command_key: String(result.metadata.command_key),
    }
  }

  return { action_type: 'unsupported' }
}

export function getUnsupportedActionMessage(): string {
  return 'This action is not supported yet.'
}
