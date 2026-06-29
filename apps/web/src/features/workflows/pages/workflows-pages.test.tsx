import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { WorkflowInstanceDetail } from '@/features/workflows/components/WorkflowInstanceDetail'
import * as workflowsApi from '@/api/endpoints/workflows'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['workflow.read', 'workflow.execute'],
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

function renderWithProviders(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)
  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('workflow pages', () => {
  it('renders instance detail with timeline section', () => {
    vi.spyOn(workflowsApi, 'cancelWorkflowInstance').mockResolvedValue({
      public_id: 'inst-1',
      definition_public_id: 'def-1',
      status: 'cancelled',
    })

    renderWithProviders(
      <WorkflowInstanceDetail
        instance={{
          public_id: 'inst-1',
          definition_public_id: 'def-1',
          definition_name: 'Onboarding',
          status: 'running',
          current_node_id: 'step-1',
        }}
        history={{ steps: [{ node_id: 'step-1', status: 'running', started_at: '2024-01-01' }], events: [], logs: [] }}
        permissions={['workflow.read', 'workflow.execute']}
      />,
    )

    expect(screen.getByTestId('workflow-instance-detail')).toBeInTheDocument()
    expect(screen.getByTestId('workflow-timeline')).toBeInTheDocument()
  })

  it('blocks instance detail without permission', () => {
    renderWithProviders(
      <WorkflowInstanceDetail
        instance={{
          public_id: 'inst-1',
          definition_public_id: 'def-1',
          status: 'running',
        }}
        history={null}
        permissions={['task.read']}
      />,
    )

    expect(screen.getByText(/do not have permission/i)).toBeInTheDocument()
  })
})
