import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { NotificationCenter } from '@/features/notifications/components/NotificationCenter'
import { NotificationBell } from '@/features/notifications/components/NotificationBell'
import { NotificationCenterPage } from '@/features/notifications/pages/NotificationCenterPage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as notificationsApi from '@/api/endpoints/notifications'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>('@tanstack/react-router')
  return {
    ...actual,
    useNavigate: () => vi.fn(),
    Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
  }
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['notifications.read', 'notifications.manage'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 2,
  warnings: [],
  source: 'runtime',
}

function renderNotifications(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)
  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('notification center integration', () => {
  beforeEach(() => {
    vi.spyOn(notificationsApi, 'fetchNotifications').mockResolvedValue([
      {
        public_id: 'n-1',
        title: 'Document shared',
        body: 'A document was shared with you',
        status: 'delivered',
        priority: 'normal',
        category: 'document',
        read_at: null,
        created_at: '2024-01-01',
        metadata: { document_public_id: 'doc-1' },
      },
    ])
    vi.spyOn(notificationsApi, 'markNotificationRead').mockResolvedValue({
      public_id: 'n-1',
      title: 'Document shared',
      body: 'A document was shared with you',
      read_at: '2024-01-02',
    })
    vi.spyOn(notificationsApi, 'markAllNotificationsRead').mockRejectedValue(new Error('read-all unavailable'))
  })

  it('renders notification center with items', async () => {
    renderNotifications(<NotificationCenter />)
    await waitFor(() => {
      expect(screen.getByTestId('notification-center')).toBeInTheDocument()
    })
    await waitFor(() => {
      expect(screen.getByText('Document shared')).toBeInTheDocument()
    })
  })

  it('renders notification center page', async () => {
    renderNotifications(<NotificationCenterPage />)
    await waitFor(() => {
      expect(screen.getByTestId('notification-center')).toBeInTheDocument()
    })
  })

  it('shows bell preview and opens center link', async () => {
    const user = userEvent.setup()
    renderNotifications(<NotificationBell />)

    await user.click(screen.getByLabelText(/Notifications/))
    expect(screen.getByRole('menu', { name: 'Notification preview' })).toBeInTheDocument()
    expect(screen.getByText('Open Notification Center')).toBeInTheDocument()
  })
})
