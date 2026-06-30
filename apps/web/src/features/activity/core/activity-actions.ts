import type { ActivityAction, ActivityEntry } from '@/api/types/activity'

export function resolveActivityRoute(entry: ActivityEntry): string | null {
  if (entry.entity?.route) return entry.entity.route

  const entityType = entry.entity?.type
  const publicId = entry.entity?.public_id
  const metadata = entry.metadata ?? {}
  const moduleKey = entry.entity?.module_key ?? entry.module_key ?? (typeof metadata.module_key === 'string' ? metadata.module_key : null)
  const entityKey = entry.entity?.entity_key ?? (typeof metadata.entity_key === 'string' ? metadata.entity_key : null)

  if (!publicId && !moduleKey) return null

  switch (entityType) {
    case 'document':
      return publicId ? `/documents/${publicId}` : '/documents'
    case 'workflow':
      return publicId ? `/workflows/instances/${publicId}` : '/workflows'
    case 'task':
      return publicId ? `/workflows/tasks/${publicId}` : '/workflows'
    case 'approval':
      return publicId ? `/workflows/approvals/${publicId}` : '/workflows'
    case 'report':
      return moduleKey && typeof metadata.report_key === 'string'
        ? `/reports/${moduleKey}/${metadata.report_key}`
        : null
    case 'dashboard':
      return moduleKey && typeof metadata.dashboard_key === 'string'
        ? `/dashboards/${moduleKey}/${metadata.dashboard_key}`
        : null
    case 'form':
      return moduleKey && typeof metadata.form_key === 'string'
        ? `/forms/${moduleKey}/${metadata.form_key}`
        : null
    case 'table':
      return moduleKey && typeof metadata.table_key === 'string'
        ? `/tables/${moduleKey}/${metadata.table_key}`
        : null
    case 'notification':
      return publicId ? `/notifications/${publicId}` : '/notifications'
    case 'record':
      return moduleKey && entityKey ? `/app/${moduleKey}/${entityKey}` : null
    default:
      return publicId ? `/activity/${entityType ?? 'custom'}/${publicId}` : '/activity'
  }
}

export function resolveActivityAction(entry: ActivityEntry): ActivityAction {
  const route = resolveActivityRoute(entry)
  if (route) {
    return { action_key: 'open_resource', label: 'Open resource', route, metadata: entry.metadata }
  }
  return { action_key: 'unsupported', label: 'View details', metadata: entry.metadata }
}

export function getUnsupportedActivityActionMessage(): string {
  return 'This activity action is not supported yet.'
}
