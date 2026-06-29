import { describe, expect, it, vi, beforeEach } from 'vitest'
import {
  approveRequest,
  cancelHumanTask,
  completeHumanTask,
  fetchApproval,
  fetchApprovals,
  fetchHumanTask,
  fetchHumanTaskComments,
  fetchHumanTaskInbox,
  fetchHumanTasks,
  fetchWorkflowInstance,
  fetchWorkflowInstances,
  openHumanTask,
  rejectRequest,
} from '@/api/endpoints/workflows'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('workflow API endpoints', () => {
  beforeEach(() => {
    vi.mocked(apiClient.get).mockReset()
    vi.mocked(apiClient.post).mockReset()
  })

  it('fetches human task inbox', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: [{ public_id: 'task-1', title: 'Task', status: 'assigned', task_type: 'review' }],
    })

    const tasks = await fetchHumanTaskInbox('assigned')
    expect(tasks[0].public_id).toBe('task-1')
  })

  it('fetches human tasks with query params', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: [{ public_id: 'task-1', title: 'Task', status: 'assigned', task_type: 'review' }],
    })

    const tasks = await fetchHumanTasks({ status: 'assigned' })
    expect(tasks).toHaveLength(1)
  })

  it('fetches a human task by id', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { public_id: 'task-1', title: 'Task', status: 'assigned', task_type: 'review' },
    })

    const task = await fetchHumanTask('task-1')
    expect(task.public_id).toBe('task-1')
  })

  it('opens, completes, and cancels human tasks', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({
      data: { public_id: 'task-1', title: 'Task', status: 'opened', task_type: 'review' },
    })

    await openHumanTask('task-1')
    await completeHumanTask('task-1', { result: { approved: true } })
    await cancelHumanTask('task-1')

    expect(apiClient.post).toHaveBeenCalledTimes(3)
  })

  it('fetches task comments', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: [{ public_id: 'c-1', body: 'Note', created_at: '2024-01-01' }],
    })

    const comments = await fetchHumanTaskComments('task-1')
    expect(comments[0].body).toBe('Note')
  })

  it('fetches approvals and approval detail', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: [{ public_id: 'ap-1', title: 'Approval', status: 'pending', approval_type: 'manager' }],
    })
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { public_id: 'ap-1', title: 'Approval', status: 'pending', approval_type: 'manager' },
    })

    const approvals = await fetchApprovals({ status: 'pending' })
    const approval = await fetchApproval('ap-1')
    expect(approvals[0].public_id).toBe('ap-1')
    expect(approval.public_id).toBe('ap-1')
  })

  it('approves and rejects requests', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({ data: { status: 'approved' } })
    await approveRequest('ap-1', { comment: 'ok' })
    await rejectRequest('ap-1', { comment: 'no' })
    expect(apiClient.post).toHaveBeenCalledTimes(2)
  })

  it('fetches workflow instances and instance detail', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: [{ public_id: 'inst-1', definition_public_id: 'def-1', status: 'running' }],
    })
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { public_id: 'inst-1', definition_public_id: 'def-1', status: 'running' },
    })

    const instances = await fetchWorkflowInstances({ status: 'running' })
    const instance = await fetchWorkflowInstance('inst-1')
    expect(instances[0].public_id).toBe('inst-1')
    expect(instance.public_id).toBe('inst-1')
  })
})
