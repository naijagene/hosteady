import { describe, expect, it } from 'vitest'
import {
  normalizeApproval,
  normalizeHumanTask,
  normalizeHumanTaskComment,
  normalizeHumanTaskHistory,
  normalizeWorkflowBindingContext,
  normalizeWorkflowDefinition,
  normalizeWorkflowInstance,
  normalizeWorkflowInstanceHistory,
} from '@/api/types/workflows'

describe('workflow API type normalization', () => {
  it('normalizes workflow definition snake_case', () => {
    const definition = normalizeWorkflowDefinition({
      public_id: 'def-1',
      workflow_key: 'onboarding',
      name: 'Onboarding',
      status: 'active',
    })

    expect(definition.public_id).toBe('def-1')
    expect(definition.workflow_key).toBe('onboarding')
  })

  it('normalizes workflow instance camelCase', () => {
    const instance = normalizeWorkflowInstance({
      publicId: 'inst-1',
      definitionPublicId: 'def-1',
      definitionName: 'Onboarding',
      status: 'running',
      currentNodeId: 'step-1',
      durationMs: 1000,
    })

    expect(instance.public_id).toBe('inst-1')
    expect(instance.current_node_id).toBe('step-1')
  })

  it('normalizes human task and comments', () => {
    const task = normalizeHumanTask({
      publicId: 'task-1',
      title: 'Review',
      status: 'assigned',
      taskType: 'review',
      commentsCount: 2,
    })

    expect(task.public_id).toBe('task-1')
    expect(task.comments_count).toBe(2)

    const comment = normalizeHumanTaskComment({
      publicId: 'c-1',
      body: 'Note',
      createdAt: '2024-01-01',
    })

    expect(comment.public_id).toBe('c-1')
  })

  it('normalizes approval and history', () => {
    const approval = normalizeApproval({
      publicId: 'ap-1',
      title: 'Approve budget',
      status: 'pending',
      approvalType: 'manager',
    })

    expect(approval.public_id).toBe('ap-1')

    const history = normalizeHumanTaskHistory({
      eventType: 'opened',
      occurredAt: '2024-01-01',
      summary: 'Opened',
    })

    expect(history.event_type).toBe('opened')
  })

  it('normalizes instance history and binding context', () => {
    const history = normalizeWorkflowInstanceHistory({
      steps: [{ nodeId: 'a', status: 'completed', startedAt: '2024-01-01' }],
      events: [{ eventType: 'started', occurredAt: '2024-01-01' }],
      logs: [{ level: 'info', message: 'Started' }],
    })

    expect(history.steps?.[0].node_id).toBe('a')

    const binding = normalizeWorkflowBindingContext({
      mode: 'approvals',
      showCounts: true,
      perPage: 10,
    })

    expect(binding.mode).toBe('approvals')
    expect(binding.show_counts).toBe(true)
    expect(binding.per_page).toBe(10)
  })
})
