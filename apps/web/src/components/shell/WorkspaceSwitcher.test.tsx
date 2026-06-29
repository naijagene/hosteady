import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { WorkspaceSwitcher } from '@/components/shell/WorkspaceSwitcher'
import { useAuthStore } from '@/stores/auth-store'

vi.mock('@/features/auth/services/session-service', () => ({
  switchWorkspace: vi.fn(async () => undefined),
}))

describe('WorkspaceSwitcher', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
  })

  it('shows current workspace label for single-workspace tenants', () => {
    useAuthStore.getState().setOrganization({
      public_id: 'org-1',
      name: 'Acme',
      slug: 'acme',
      status: 'active',
      organization_code: 'ACM001',
      membership: {
        public_id: 'mem-1',
        status: 'active',
        join_method: 'invite',
        default_workspace_public_id: 'ws-1',
      },
    })
    useAuthStore.getState().setWorkspace({
      public_id: 'ws-1',
      name: 'Operations',
      slug: 'operations',
      is_default: true,
      status: 'active',
    })
    useAuthStore.getState().setWorkspaces([
      {
        public_id: 'ws-1',
        name: 'Operations',
        slug: 'operations',
        is_default: true,
        status: 'active',
      },
    ])

    render(<WorkspaceSwitcher />)
    expect(screen.getByText('Operations')).toBeInTheDocument()
  })

  it('renders a selector when multiple workspaces exist', async () => {
    useAuthStore.getState().setWorkspaces([
      {
        public_id: 'ws-1',
        name: 'Operations',
        slug: 'operations',
        is_default: true,
        status: 'active',
      },
      {
        public_id: 'ws-2',
        name: 'Finance',
        slug: 'finance',
        is_default: false,
        status: 'active',
      },
    ])
    useAuthStore.getState().setWorkspace({
      public_id: 'ws-1',
      name: 'Operations',
      slug: 'operations',
      is_default: true,
      status: 'active',
    })

    render(<WorkspaceSwitcher />)
    const select = screen.getByLabelText('Workspace')
    await userEvent.selectOptions(select, 'ws-2')

    const { switchWorkspace } = await import('@/features/auth/services/session-service')
    expect(switchWorkspace).toHaveBeenCalledWith('ws-2')
  })
})
