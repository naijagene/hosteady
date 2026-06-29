import type { Notification } from '@/api/types/notifications'

export function resolveNotificationLink(notification: Notification): string | null {
  const metadata = notification.metadata ?? {}
  const mergeData = notification.merge_data ?? {}

  const direct =
    (typeof metadata.route === 'string' ? metadata.route : null) ??
    (typeof metadata.deep_link === 'string' ? metadata.deep_link : null) ??
    (typeof metadata.href === 'string' ? metadata.href : null) ??
    (typeof mergeData.route === 'string' ? mergeData.route : null)

  if (direct) {
    return direct
  }

  const taskPublicId = metadata.task_public_id ?? metadata.workflow_task_public_id
  if (typeof taskPublicId === 'string' && taskPublicId) {
    return `/workflows/tasks/${taskPublicId}`
  }

  const approvalPublicId = metadata.approval_public_id
  if (typeof approvalPublicId === 'string' && approvalPublicId) {
    return `/workflows/approvals/${approvalPublicId}`
  }

  const instancePublicId = metadata.workflow_instance_public_id
  if (typeof instancePublicId === 'string' && instancePublicId) {
    return `/workflows/instances/${instancePublicId}`
  }

  const documentPublicId = metadata.document_public_id
  if (typeof documentPublicId === 'string' && documentPublicId) {
    return `/documents/${documentPublicId}`
  }

  const reportKey = metadata.report_key
  const moduleKey = metadata.module_key
  if (typeof reportKey === 'string' && typeof moduleKey === 'string') {
    return `/reports/${moduleKey}/${reportKey}`
  }

  const dashboardKey = metadata.dashboard_key
  if (typeof dashboardKey === 'string' && typeof moduleKey === 'string') {
    return `/dashboards/${moduleKey}/${dashboardKey}`
  }

  return `/notifications/${notification.public_id}`
}

export function getNotificationActionLabel(notification: Notification): string {
  const metadata = notification.metadata ?? {}
  const eventType = typeof metadata.event_type === 'string' ? metadata.event_type : notification.template_key

  switch (eventType) {
    case 'workflow.approval.pending':
    case 'approval.pending':
      return 'Review approval'
    case 'workflow.task.assigned':
    case 'task.assigned':
      return 'Open task'
    case 'workflow.task.overdue':
    case 'task.overdue':
      return 'View overdue task'
    case 'workflow.completed':
      return 'View workflow'
    case 'document.uploaded':
    case 'document.shared':
      return 'Open document'
    case 'report.generated':
    case 'report.export.completed':
      return 'Open report'
    default:
      return 'View details'
  }
}
