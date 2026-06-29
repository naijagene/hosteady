import { describe, expect, it } from 'vitest'
import {
  getWorkflowStatusLabel,
  getWorkflowStatusTone,
  normalizeWorkflowStatus,
} from '@/features/workflows/core/workflow-status'
import {
  canDecideApprovals,
  canExecuteWorkflows,
  canManageTasks,
  canReadApprovals,
  canReadTasks,
  canReadWorkflows,
} from '@/features/workflows/core/workflow-permissions'
import { filterWorkflowItems, resolveInboxQuery } from '@/features/workflows/core/workflow-query'
import { buildWorkflowTimeline } from '@/features/workflows/core/workflow-timeline'
import { isTaskOverdue, getTaskSlaLabel } from '@/features/workflows/core/task-normalizer'

const statuses = ['pending', 'assigned', 'opened', 'in_progress', 'running', 'completed', 'failed', 'approved', 'rejected', 'cancelled']

describe('workflow status tones', () => {
  statuses.forEach((status) => {
    it(`maps tone for ${status}`, () => {
      expect(getWorkflowStatusTone(status)).toBeDefined()
      expect(getWorkflowStatusLabel(status)).toContain(status.split('_')[0])
    })
  })

  it('normalizes unknown status', () => {
    expect(normalizeWorkflowStatus(undefined)).toBe('unknown')
  })
})

describe('workflow permission matrix', () => {
  it('requires workflow.read for runtime access when permissions provided', () => {
    expect(canReadWorkflows(['other.permission'])).toBe(false)
    expect(canReadWorkflows(['workflow.read'])).toBe(true)
    expect(canReadWorkflows(['workflow.runtime.read'])).toBe(true)
  })

  it('requires task.read when permissions provided', () => {
    expect(canReadTasks(['other.permission'])).toBe(false)
    expect(canReadTasks(['task.read'])).toBe(true)
  })

  it('requires approval.read when permissions provided', () => {
    expect(canReadApprovals(['other.permission'])).toBe(false)
    expect(canReadApprovals(['approval.read'])).toBe(true)
  })

  it('requires execute permission for workflow actions', () => {
    expect(canExecuteWorkflows(['workflow.read'])).toBe(false)
    expect(canExecuteWorkflows(['workflow.execute'])).toBe(true)
  })

  it('requires manage and decide permissions', () => {
    expect(canManageTasks(['task.manage'])).toBe(true)
    expect(canDecideApprovals(['approval.decide'])).toBe(true)
  })
})

describe('inbox query resolution', () => {
  it('resolves completed and failed tabs', () => {
    expect(resolveInboxQuery('completed')).toEqual({
      taskStatus: 'completed',
      instanceStatus: 'completed',
    })
    expect(resolveInboxQuery('failed')).toEqual({ instanceStatus: 'failed' })
    expect(resolveInboxQuery('all')).toEqual({ inboxType: 'all' })
  })
})

describe('filtering', () => {
  it('filters by task type', () => {
    const items = [
      { title: 'A', status: 'assigned', task_type: 'review' },
      { title: 'B', status: 'assigned', task_type: 'approval' },
    ]

    expect(filterWorkflowItems(items, '', '', 'review')).toHaveLength(1)
  })
})

describe('timeline kinds', () => {
  it('includes all supported timeline kinds', () => {
    const entries = buildWorkflowTimeline({
      steps: [{ node_id: 's', status: 'done', started_at: '2024-01-01' }],
      events: [{ event_type: 'evt', occurred_at: '2024-01-02' }],
      logs: [{ level: 'info', message: 'log', occurred_at: '2024-01-03' }],
      comments: [{ public_id: 'c', body: 'comment', created_at: '2024-01-04' }],
      history: [{ event_type: 'hist', occurred_at: '2024-01-05' }],
    })

    const kinds = new Set(entries.map((entry) => entry.kind))
    expect(kinds.has('step')).toBe(true)
    expect(kinds.has('event')).toBe(true)
    expect(kinds.has('log')).toBe(true)
    expect(kinds.has('comment')).toBe(true)
    expect(kinds.has('history')).toBe(true)
  })
})

describe('task SLA edge cases', () => {
  it('returns null SLA when no due date', () => {
    expect(
      getTaskSlaLabel({
        public_id: 't',
        title: 'Task',
        status: 'assigned',
        task_type: 'review',
      }),
    ).toBeNull()
  })

  it('does not mark completed tasks overdue', () => {
    expect(
      isTaskOverdue({
        public_id: 't',
        title: 'Task',
        status: 'completed',
        task_type: 'review',
        due_at: '2020-01-01T00:00:00.000Z',
      }),
    ).toBe(false)
  })
})
