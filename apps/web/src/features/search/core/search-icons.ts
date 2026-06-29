import type { SearchResultType } from '@/api/types/search'

export function resolveSearchIcon(type: SearchResultType | string): string {
  switch (type) {
    case 'application':
      return 'application'
    case 'page':
    case 'navigation':
      return 'page'
    case 'document':
      return 'document'
    case 'report':
      return 'report'
    case 'dashboard':
      return 'dashboard'
    case 'workflow':
    case 'task':
    case 'approval':
      return 'workflow'
    case 'notification':
      return 'notification'
    case 'command':
      return 'command'
    case 'favorite':
      return 'favorite'
    case 'recent':
      return 'recent'
    case 'shortcut':
      return 'shortcut'
    case 'setting':
      return 'setting'
    case 'workspace':
      return 'workspace'
    case 'user':
      return 'user'
    default:
      return 'search'
  }
}
