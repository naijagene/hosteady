import type { ActivityEntityType } from '@/api/types/activity'

export function resolveActivityIcon(type?: ActivityEntityType | string | null, action?: string): string {
  const normalized = (type ?? action ?? 'custom').toLowerCase()
  switch (normalized) {
    case 'document':
      return 'document'
    case 'workflow':
    case 'task':
    case 'approval':
      return 'workflow'
    case 'report':
      return 'report'
    case 'dashboard':
      return 'dashboard'
    case 'form':
      return 'form'
    case 'table':
      return 'table'
    case 'notification':
      return 'notification'
    case 'security':
      return 'security'
    case 'user':
      return 'user'
    case 'workspace':
      return 'workspace'
    default:
      return 'activity'
  }
}
