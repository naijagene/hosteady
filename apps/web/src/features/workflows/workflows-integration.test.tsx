import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { WorkflowInbox } from '@/features/workflows/components/WorkflowInbox'
import { WorkflowInboxPage } from '@/features/workflows/pages/WorkflowInboxPage'
import { WorkflowInboxWidget } from '@/features/workflows/widgets/WorkflowInboxWidget'
import { ApprovalQueueWidget } from '@/features/workflows/widgets/ApprovalQueueWidget'
import { WorkflowStatusWidget } from '@/features/workflows/widgets/WorkflowStatusWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as workflowsApi from '@/api/endpoints/workflows'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['task.read', 'task.manage', 'approval.read', 'approval.decide', 'workflow.read'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'runtime',
}

function renderWorkflow(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('workflow inbox integration', () => {
  beforeEach(() => {
    vi.spyOn(workflowsApi, 'fetchHumanTaskInbox').mockResolvedValue([
      {
        public_id: 'task-1',
        title: 'Assigned task',
        status: 'assigned',
        task_type: 'review',
        created_at: '2024-01-01',
      },
    ])
    vi.spyOn(workflowsApi, 'fetchApprovals').mockResolvedValue([
      {
        public_id: 'ap-1',
        title: 'Pending approval',
        status: 'pending',
        approval_type: 'manager',
      },
    ])
    vi.spyOn(workflowsApi, 'fetchWorkflowInstances').mockResolvedValue([
      {
        public_id: 'inst-1',
        definition_public_id: 'def-1',
        definition_name: 'Running flow',
        status: 'running',
      },
    ])
  })

  it('renders inbox with assigned tasks', async () => {
    renderWorkflow(<WorkflowInbox />)

    await waitFor(() => {
      expect(screen.getByTestId('workflow-inbox')).toBeInTheDocument()
    })

    await waitFor(() => {
      expect(screen.getByText('Assigned task')).toBeInTheDocument()
    })
  })

  it('switches to approvals tab', async () => {
    const user = userEvent.setup()
    renderWorkflow(<WorkflowInbox />)

    await waitFor(() => {
      expect(screen.getByTestId('workflow-inbox-tabs')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('tab', { name: /Pending approvals/ }))

    await waitFor(() => {
      expect(screen.getByText('Pending approval')).toBeInTheDocument()
    })
  })

  it('renders inbox page', async () => {
    renderWorkflow(<WorkflowInboxPage />)
    await waitFor(() => {
      expect(screen.getByTestId('workflow-inbox')).toBeInTheDocument()
    })
  })
})

describe('workflow dashboard widgets', () => {
  beforeEach(() => {
    vi.spyOn(workflowsApi, 'fetchHumanTaskInbox').mockResolvedValue([])
    vi.spyOn(workflowsApi, 'fetchApprovals').mockResolvedValue([])
    vi.spyOn(workflowsApi, 'fetchWorkflowInstances').mockResolvedValue([])
  })

  it('renders workflow inbox widget', async () => {
    renderWorkflow(<WorkflowInboxWidget widget={{ widget_key: 'queue', widget_type: 'workflow_queue', label: 'Tasks' }} widgetType="workflow_queue" />)
    await waitFor(() => {
      expect(screen.getByTestId('workflow-inbox-widget')).toBeInTheDocument()
    })
  })

  it('renders approval queue widget', async () => {
    renderWorkflow(<ApprovalQueueWidget widget={{ widget_key: 'queue', widget_type: 'approval_queue', label: 'Approvals' }} widgetType="approval_queue" />)
    await waitFor(() => {
      expect(screen.getByTestId('approval-queue-widget')).toBeInTheDocument()
    })
  })

  it('renders workflow status widget', async () => {
    renderWorkflow(<WorkflowStatusWidget widget={{ widget_key: 'status', widget_type: 'workflow_status', label: 'Status' }} widgetType="workflow_status" />)
    await waitFor(() => {
      expect(screen.getByTestId('workflow-status-widget')).toBeInTheDocument()
    })
  })
})
