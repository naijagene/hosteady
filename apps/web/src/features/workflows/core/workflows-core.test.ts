import { describe, expect, it } from 'vitest'
import {
  formatWorkflowDate,
  formatWorkflowDuration,
  getApprovalDisplayTitle,
  getInstanceDisplayTitle,
  getTaskDisplayTitle,
} from '@/features/workflows/core/workflow-normalizer'
import {
  buildInboxCounts,
  createInitialWorkflowQuery,
  filterWorkflowItems,
  resolveInboxQuery,
} from '@/features/workflows/core/workflow-query'
import {
  getWorkflowStatusLabel,
  getWorkflowStatusTone,
  normalizeWorkflowStatus,
} from '@/features/workflows/core/workflow-status'
import {
  canDecideApprovals,
  canManageTasks,
  canReadApprovals,
  canReadTasks,
  canReadWorkflows,
} from '@/features/workflows/core/workflow-permissions'
import { toWorkflowQueryError } from '@/features/workflows/core/workflow-errors'
import { getTaskSlaLabel, isTaskOverdue } from '@/features/workflows/core/task-normalizer'
import { getApprovalDueLabel, getApprovalRequester } from '@/features/workflows/core/approval-normalizer'
import { buildWorkflowTimeline } from '@/features/workflows/core/workflow-timeline'
import { ApiError } from '@/api/errors'
import type { Approval, HumanTask, WorkflowInstance } from '@/api/types/workflows'

const task: HumanTask = {
  public_id: 'task-1',
  title: 'Review request',
  status: 'assigned',
  task_type: 'review',
  workflow_instance_public_id: 'inst-1',
  due_at: '2099-01-01T00:00:00.000Z',
  created_at: '2024-01-01T00:00:00.000Z',
}

const overdueTask: HumanTask = {
  ...task,
  public_id: 'task-2',
  status: 'assigned',
  due_at: '2020-01-01T00:00:00.000Z',
}

const approval: Approval = {
  public_id: 'ap-1',
  title: 'Budget approval',
  status: 'pending',
  approval_type: 'manager',
  metadata: { requester: 'Alice', due_at: '2024-12-01T00:00:00.000Z' },
}

const instance: WorkflowInstance = {
  public_id: 'inst-1',
  definition_public_id: 'def-1',
  definition_name: 'Onboarding',
  status: 'running',
  current_node_id: 'step-1',
  started_at: '2024-01-01T00:00:00.000Z',
  duration_ms: 5000,
  warnings: ['warn'],
  errors: [],
}

describe('workflow normalizer', () => {
  it('formats display titles', () => {
    expect(getTaskDisplayTitle(task)).toBe('Review request')
    expect(getApprovalDisplayTitle(approval)).toBe('Budget approval')
    expect(getInstanceDisplayTitle(instance)).toBe('Onboarding')
  })

  it('formats dates and durations', () => {
    expect(formatWorkflowDate(null)).toBe('—')
    expect(formatWorkflowDuration(5000)).toBe('5s')
    expect(formatWorkflowDuration(null)).toBe('—')
  })
})

describe('workflow query core', () => {
  it('creates initial query payload', () => {
    expect(createInitialWorkflowQuery().page).toBe(1)
  })

  it('resolves inbox tab queries', () => {
    expect(resolveInboxQuery('assigned')).toEqual({ inboxType: 'assigned' })
    expect(resolveInboxQuery('approvals')).toEqual({ approvalStatus: 'pending' })
    expect(resolveInboxQuery('running')).toEqual({ instanceStatus: 'running' })
  })

  it('filters workflow items by search and status', () => {
    const filtered = filterWorkflowItems([task], 'review', 'assigned')
    expect(filtered).toHaveLength(1)
    expect(filterWorkflowItems([task], 'missing', 'assigned')).toHaveLength(0)
  })

  it('builds inbox counts', () => {
    const counts = buildInboxCounts([task], [approval], [instance])
    expect(counts.assigned).toBeGreaterThan(0)
    expect(counts.approvals).toBe(1)
    expect(counts.running).toBe(1)
  })
})

describe('workflow status core', () => {
  it('normalizes status values', () => {
    expect(normalizeWorkflowStatus('Running')).toBe('running')
  })

  it('maps status labels and tones', () => {
    expect(getWorkflowStatusLabel('in_progress')).toBe('in progress')
    expect(getWorkflowStatusTone('completed')).toBe('success')
    expect(getWorkflowStatusTone('failed')).toBe('danger')
    expect(getWorkflowStatusTone('pending')).toBe('warning')
  })
})

describe('workflow permissions', () => {
  it('allows read when permissions empty', () => {
    expect(canReadTasks([])).toBe(true)
    expect(canReadApprovals([])).toBe(true)
    expect(canReadWorkflows([])).toBe(true)
  })

  it('requires explicit manage/decide permissions', () => {
    expect(canManageTasks(['task.read'])).toBe(false)
    expect(canManageTasks(['task.manage'])).toBe(true)
    expect(canDecideApprovals(['approval.decide'])).toBe(true)
  })
})

describe('workflow errors', () => {
  it('maps ApiError messages', () => {
    const error = toWorkflowQueryError(new ApiError('Failed', { status: 422 }))
    expect(error.message).toBe('Failed')
    expect(error.status).toBe(422)
  })
})

describe('task normalizer', () => {
  it('detects overdue tasks', () => {
    expect(isTaskOverdue(overdueTask)).toBe(true)
    expect(isTaskOverdue(task)).toBe(false)
  })

  it('builds SLA labels', () => {
    expect(getTaskSlaLabel(overdueTask)).toBe('Overdue')
    expect(getTaskSlaLabel(task)).toContain('Due')
  })
})

describe('approval normalizer', () => {
  it('extracts requester and due labels', () => {
    expect(getApprovalRequester(approval)).toBe('Alice')
    expect(getApprovalDueLabel(approval)).toContain('Due')
  })
})

describe('workflow timeline', () => {
  it('merges and sorts timeline entries', () => {
    const entries = buildWorkflowTimeline({
      steps: [{ node_id: 'a', status: 'completed', started_at: '2024-01-01', completed_at: '2024-01-02' }],
      comments: [{ public_id: 'c-1', body: 'Note', created_at: '2024-01-03' }],
      history: [{ event_type: 'opened', occurred_at: '2024-01-04', summary: 'Opened' }],
    })

    expect(entries[0].occurred_at).toBe('2024-01-04')
    expect(entries.some((entry) => entry.kind === 'comment')).toBe(true)
  })
})
