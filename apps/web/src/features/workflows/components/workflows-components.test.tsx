import { describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { WorkflowStatusBadge } from '@/features/workflows/components/WorkflowStatusBadge'
import { WorkflowSlaBadge } from '@/features/workflows/components/WorkflowSlaBadge'
import { WorkflowEmptyState } from '@/features/workflows/components/WorkflowEmptyState'
import { WorkflowLoadingState } from '@/features/workflows/components/WorkflowLoadingState'
import { WorkflowErrorState } from '@/features/workflows/components/WorkflowErrorState'
import { WorkflowTaskCard } from '@/features/workflows/components/WorkflowTaskCard'
import { WorkflowTaskList } from '@/features/workflows/components/WorkflowTaskList'
import { WorkflowApprovalCard } from '@/features/workflows/components/WorkflowApprovalCard'
import { WorkflowApprovalList } from '@/features/workflows/components/WorkflowApprovalList'
import { WorkflowInstanceCard } from '@/features/workflows/components/WorkflowInstanceCard'
import { WorkflowInstanceList } from '@/features/workflows/components/WorkflowInstanceList'
import { WorkflowInboxTabs } from '@/features/workflows/components/WorkflowInboxTabs'
import { WorkflowTimeline } from '@/features/workflows/components/WorkflowTimeline'
import { WorkflowCommentList } from '@/features/workflows/components/WorkflowCommentList'
import { WorkflowCommentComposer } from '@/features/workflows/components/WorkflowCommentComposer'
import { WorkflowTaskActions } from '@/features/workflows/components/WorkflowTaskActions'
import { WorkflowApprovalDialog } from '@/features/workflows/components/WorkflowApprovalDialog'
import * as workflowsApi from '@/api/endpoints/workflows'

const task = {
  public_id: 'task-1',
  title: 'Review request',
  status: 'assigned',
  task_type: 'review',
  priority: 'high',
  workflow_instance_public_id: 'inst-1',
  created_at: '2024-01-01T00:00:00.000Z',
  comments_count: 2,
}

const approval = {
  public_id: 'ap-1',
  title: 'Budget approval',
  status: 'pending',
  approval_type: 'manager',
  requested_at: '2024-01-01T00:00:00.000Z',
  metadata: { requester: 'Alice' },
}

const instance = {
  public_id: 'inst-1',
  definition_public_id: 'def-1',
  definition_name: 'Onboarding',
  status: 'running',
  current_node_id: 'step-1',
  started_at: '2024-01-01T00:00:00.000Z',
  duration_ms: 5000,
  warnings: ['warn'],
  errors: ['err'],
}

function renderWithClient(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>)
}

describe('workflow badges and states', () => {
  it('renders status badge with aria label', () => {
    render(<WorkflowStatusBadge status="running" />)
    expect(screen.getByTestId('workflow-status-badge')).toHaveAttribute('aria-label', 'Status running')
  })

  it('renders SLA badge', () => {
    render(<WorkflowSlaBadge label="Overdue" overdue />)
    expect(screen.getByTestId('workflow-sla-badge')).toHaveTextContent('Overdue')
  })

  it('renders empty, loading, and error states', () => {
    render(<WorkflowEmptyState message="Nothing here" />)
    expect(screen.getByTestId('workflow-empty-state')).toHaveTextContent('Nothing here')

    render(<WorkflowLoadingState />)
    expect(screen.getByTestId('workflow-loading-state')).toHaveAttribute('aria-busy', 'true')

    render(<WorkflowErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
  })
})

describe('workflow lists and cards', () => {
  it('renders task card and list', () => {
    const onOpen = vi.fn()
    render(<WorkflowTaskCard task={task} onOpen={onOpen} />)
    expect(screen.getByText('Review request')).toBeInTheDocument()
    expect(screen.getByText('2')).toBeInTheDocument()

    render(<WorkflowTaskList tasks={[task]} onOpen={onOpen} />)
    expect(screen.getByTestId('workflow-task-list')).toBeInTheDocument()
  })

  it('renders approval card and list', () => {
    render(<WorkflowApprovalCard approval={approval} onOpen={vi.fn()} />)
    expect(screen.getByText('Budget approval')).toBeInTheDocument()

    render(<WorkflowApprovalList approvals={[approval]} />)
    expect(screen.getByTestId('workflow-approval-list')).toBeInTheDocument()
  })

  it('renders instance card and list', () => {
    render(<WorkflowInstanceCard instance={instance} onOpen={vi.fn()} />)
    expect(screen.getByText('Onboarding')).toBeInTheDocument()

    render(<WorkflowInstanceList instances={[instance]} />)
    expect(screen.getByTestId('workflow-instance-list')).toBeInTheDocument()
  })

  it('renders empty lists', () => {
    render(<WorkflowTaskList tasks={[]} emptyMessage="No tasks" />)
    expect(screen.getByText('No tasks')).toBeInTheDocument()
  })
})

describe('workflow inbox tabs', () => {
  it('renders tabs with counts and selection', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()

    render(
      <WorkflowInboxTabs
        activeTab="assigned"
        counts={{ assigned: 3, approvals: 1, running: 2, completed: 0, failed: 0, all: 4 }}
        onChange={onChange}
      />,
    )

    expect(screen.getByRole('tab', { name: /Assigned to me \(3\)/ })).toHaveAttribute('aria-selected', 'true')
    await user.click(screen.getByRole('tab', { name: /Pending approvals \(1\)/ }))
    expect(onChange).toHaveBeenCalledWith('approvals')
  })
})

describe('workflow timeline and comments', () => {
  it('renders timeline entries', () => {
    render(
      <WorkflowTimeline
        history={[{ event_type: 'opened', occurred_at: '2024-01-01', summary: 'Opened' }]}
      />,
    )
    expect(screen.getByTestId('workflow-timeline')).toBeInTheDocument()
  })

  it('renders comment list and composer', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn().mockResolvedValue(undefined)

    render(
      <WorkflowCommentList
        comments={[{ public_id: 'c-1', body: 'Hello', created_at: '2024-01-01' }]}
      />,
    )
    expect(screen.getByText('Hello')).toBeInTheDocument()

    render(<WorkflowCommentComposer onSubmit={onSubmit} />)
    await user.type(screen.getByLabelText('Comment text'), 'New note')
    await user.click(screen.getByRole('button', { name: 'Post comment' }))
    expect(onSubmit).toHaveBeenCalledWith('New note')
  })
})

describe('workflow task actions and approval dialog', () => {
  it('disables actions without manage permission', () => {
    render(
      <WorkflowTaskActions
        task={task}
        permissions={['task.read']}
        onOpen={vi.fn()}
        onComplete={vi.fn()}
        onCancel={vi.fn()}
      />,
    )

    expect(screen.getByRole('button', { name: /Complete task/ })).toBeDisabled()
  })

  it('approves and rejects in dialog', async () => {
    const user = userEvent.setup()
    vi.spyOn(workflowsApi, 'approveRequest').mockResolvedValue({ status: 'approved' })
    vi.spyOn(workflowsApi, 'rejectRequest').mockResolvedValue({ status: 'rejected' })

    renderWithClient(
      <WorkflowApprovalDialog
        approval={approval}
        open
        permissions={['approval.decide']}
        onClose={vi.fn()}
      />,
    )

    await user.type(screen.getByLabelText('Approval decision comment'), 'Looks good')
    await user.click(screen.getByRole('button', { name: 'Approve' }))

    await waitFor(() => {
      expect(workflowsApi.approveRequest).toHaveBeenCalled()
    })
  })
})
