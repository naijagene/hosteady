import type { Approval, HumanTask, WorkflowInstance, WorkflowQueryPayload } from '@/api/types/workflows'
import type { WorkflowInboxTab } from '../types'

export function createInitialWorkflowQuery(overrides?: Partial<WorkflowQueryPayload>): WorkflowQueryPayload {
  return {
    page: 1,
    per_page: 25,
    search: '',
    metadata: { source: 'web' },
    ...overrides,
  }
}

export function filterWorkflowItems<T extends { title?: string; status?: string; task_type?: string }>(
  items: T[],
  search: string,
  statusFilter?: string,
  taskTypeFilter?: string,
): T[] {
  const normalizedSearch = search.trim().toLowerCase()

  return items.filter((item) => {
    if (statusFilter && item.status !== statusFilter) {
      return false
    }

    if (taskTypeFilter && 'task_type' in item && item.task_type !== taskTypeFilter) {
      return false
    }

    if (!normalizedSearch) {
      return true
    }

    return Object.values(item)
      .filter((value) => typeof value === 'string')
      .some((value) => value.toLowerCase().includes(normalizedSearch))
  })
}

export function resolveInboxQuery(tab: WorkflowInboxTab): {
  inboxType?: string
  instanceStatus?: string
  taskStatus?: string
  approvalStatus?: string
} {
  switch (tab) {
    case 'assigned':
      return { inboxType: 'assigned' }
    case 'approvals':
      return { approvalStatus: 'pending' }
    case 'running':
      return { instanceStatus: 'running' }
    case 'completed':
      return { taskStatus: 'completed', instanceStatus: 'completed' }
    case 'failed':
      return { instanceStatus: 'failed' }
    case 'all':
    default:
      return { inboxType: 'all' }
  }
}

export function buildInboxCounts(
  tasks: HumanTask[],
  approvals: Approval[],
  instances: WorkflowInstance[],
): Record<WorkflowInboxTab, number> {
  return {
    assigned: tasks.filter((task) => ['assigned', 'opened', 'in_progress'].includes(task.status ?? '')).length,
    approvals: approvals.filter((approval) => approval.status === 'pending').length,
    running: instances.filter((instance) => instance.status === 'running').length,
    completed: instances.filter((instance) => instance.status === 'completed').length,
    failed: instances.filter((instance) => instance.status === 'failed').length,
    all: tasks.length + approvals.length + instances.length,
  }
}
