import { beforeEach, describe, expect, it, vi } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useHumanTask, useHumanTasks } from '@/features/workflows/hooks/useHumanTasks'
import { useApproval, useApprovals } from '@/features/workflows/hooks/useApprovals'
import { useWorkflowInstance, useWorkflowInstances } from '@/features/workflows/hooks/useWorkflowInstances'
import { useTaskActions } from '@/features/workflows/hooks/useTaskActions'
import { useApprovalActions } from '@/features/workflows/hooks/useApprovalActions'
import { useWorkflowComments } from '@/features/workflows/hooks/useWorkflowComments'
import { useWorkflowInbox } from '@/features/workflows/hooks/useWorkflowInbox'
import * as workflowsApi from '@/api/endpoints/workflows'

function createWrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
  )
}

describe('workflow hooks', () => {
  beforeEach(() => {
    vi.spyOn(workflowsApi, 'fetchHumanTasks').mockResolvedValue([
      { public_id: 'task-1', title: 'Task', status: 'assigned', task_type: 'review' },
    ])
    vi.spyOn(workflowsApi, 'fetchHumanTask').mockResolvedValue({
      public_id: 'task-1',
      title: 'Task',
      status: 'assigned',
      task_type: 'review',
    })
    vi.spyOn(workflowsApi, 'fetchApprovals').mockResolvedValue([
      { public_id: 'ap-1', title: 'Approval', status: 'pending', approval_type: 'manager' },
    ])
    vi.spyOn(workflowsApi, 'fetchApproval').mockResolvedValue({
      public_id: 'ap-1',
      title: 'Approval',
      status: 'pending',
      approval_type: 'manager',
    })
    vi.spyOn(workflowsApi, 'fetchWorkflowInstances').mockResolvedValue([
      { public_id: 'inst-1', definition_public_id: 'def-1', status: 'running' },
    ])
    vi.spyOn(workflowsApi, 'fetchWorkflowInstance').mockResolvedValue({
      public_id: 'inst-1',
      definition_public_id: 'def-1',
      status: 'running',
    })
    vi.spyOn(workflowsApi, 'fetchWorkflowInstanceHistory').mockResolvedValue({ steps: [], events: [], logs: [] })
    vi.spyOn(workflowsApi, 'fetchHumanTaskInbox').mockResolvedValue([
      { public_id: 'task-1', title: 'Task', status: 'assigned', task_type: 'review' },
    ])
    vi.spyOn(workflowsApi, 'fetchHumanTaskComments').mockResolvedValue([
      { public_id: 'c-1', body: 'Note', created_at: '2024-01-01' },
    ])
    vi.spyOn(workflowsApi, 'openHumanTask').mockResolvedValue({
      public_id: 'task-1',
      title: 'Task',
      status: 'opened',
      task_type: 'review',
    })
    vi.spyOn(workflowsApi, 'completeHumanTask').mockResolvedValue({
      public_id: 'task-1',
      title: 'Task',
      status: 'completed',
      task_type: 'review',
    })
    vi.spyOn(workflowsApi, 'cancelHumanTask').mockResolvedValue({
      public_id: 'task-1',
      title: 'Task',
      status: 'cancelled',
      task_type: 'review',
    })
    vi.spyOn(workflowsApi, 'addHumanTaskComment').mockResolvedValue({
      public_id: 'c-2',
      body: 'Added',
      created_at: '2024-01-02',
    })
    vi.spyOn(workflowsApi, 'approveRequest').mockResolvedValue({ status: 'approved' })
    vi.spyOn(workflowsApi, 'rejectRequest').mockResolvedValue({ status: 'rejected' })
  })

  it('loads human tasks', async () => {
    const { result } = renderHook(() => useHumanTasks(), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.tasks).toHaveLength(1))
  })

  it('loads a human task by id', async () => {
    const { result } = renderHook(() => useHumanTask('task-1'), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.task?.public_id).toBe('task-1'))
  })

  it('loads approvals', async () => {
    const { result } = renderHook(() => useApprovals({ status: 'pending' }), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.approvals).toHaveLength(1))
  })

  it('loads approval by id', async () => {
    const { result } = renderHook(() => useApproval('ap-1'), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.approval?.public_id).toBe('ap-1'))
  })

  it('loads workflow instances', async () => {
    const { result } = renderHook(() => useWorkflowInstances({ status: 'running' }), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.instances).toHaveLength(1))
  })

  it('loads workflow instance detail and history', async () => {
    const { result } = renderHook(() => useWorkflowInstance('inst-1'), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.instance?.public_id).toBe('inst-1'))
    expect(result.current.history).toEqual({ steps: [], events: [], logs: [] })
  })

  it('loads workflow inbox data', async () => {
    const { result } = renderHook(() => useWorkflowInbox({ show_counts: true }), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.tasks.length).toBeGreaterThan(0))
    expect(result.current.counts.assigned).toBeGreaterThan(0)
  })

  it('performs task actions', async () => {
    const { result } = renderHook(() => useTaskActions(), { wrapper: createWrapper() })
    await result.current.openTask('task-1')
    await result.current.completeTask({ publicId: 'task-1' })
    await result.current.cancelTask('task-1')
    expect(workflowsApi.openHumanTask).toHaveBeenCalled()
    expect(workflowsApi.completeHumanTask).toHaveBeenCalled()
    expect(workflowsApi.cancelHumanTask).toHaveBeenCalled()
  })

  it('performs approval actions', async () => {
    const { result } = renderHook(() => useApprovalActions(), { wrapper: createWrapper() })
    await result.current.approve({ publicId: 'ap-1', payload: { comment: 'ok' } })
    await result.current.reject({ publicId: 'ap-1', payload: { comment: 'no' } })
    expect(workflowsApi.approveRequest).toHaveBeenCalled()
    expect(workflowsApi.rejectRequest).toHaveBeenCalled()
  })

  it('loads and posts comments', async () => {
    const { result } = renderHook(() => useWorkflowComments('task-1'), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.comments).toHaveLength(1))
    await result.current.addComment('Added')
    expect(workflowsApi.addHumanTaskComment).toHaveBeenCalledWith('task-1', 'Added')
  })
})
