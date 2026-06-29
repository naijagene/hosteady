import { describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { NotificationCard } from '@/features/notifications/components/NotificationCard'
import { NotificationList } from '@/features/notifications/components/NotificationList'
import { NotificationTabs } from '@/features/notifications/components/NotificationTabs'
import { NotificationSearch } from '@/features/notifications/components/NotificationSearch'
import { NotificationFilters } from '@/features/notifications/components/NotificationFilters'
import { NotificationToolbar } from '@/features/notifications/components/NotificationToolbar'
import { NotificationDetail } from '@/features/notifications/components/NotificationDetail'
import { NotificationDrawer } from '@/features/notifications/components/NotificationDrawer'
import { NotificationEmptyState } from '@/features/notifications/components/NotificationEmptyState'
import { NotificationLoadingState } from '@/features/notifications/components/NotificationLoadingState'
import { NotificationErrorState } from '@/features/notifications/components/NotificationErrorState'
import { NotificationBadge } from '@/features/notifications/components/NotificationBadge'
import * as notificationsApi from '@/api/endpoints/notifications'

const notification = {
  public_id: 'n-1',
  title: 'Report ready',
  body: 'Your export completed',
  status: 'delivered',
  priority: 'normal',
  category: 'report',
  read_at: null,
  created_at: '2024-01-01T00:00:00.000Z',
  metadata: { report_key: 'summary', module_key: 'platform', event_type: 'report.generated' },
}

function renderWithClient(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>)
}

describe('notification UI states', () => {
  it('renders empty, loading, and error states', () => {
    render(<NotificationEmptyState message="Nothing here" />)
    expect(screen.getByTestId('notification-empty-state')).toHaveTextContent('Nothing here')

    render(<NotificationLoadingState />)
    expect(screen.getByTestId('notification-loading-state')).toHaveAttribute('aria-busy', 'true')

    render(<NotificationErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
  })

  it('renders unread badge', () => {
    render(<NotificationBadge count={3} />)
    expect(screen.getByTestId('notification-badge')).toHaveTextContent('3')
  })
})

describe('notification list and card', () => {
  it('renders notification card actions', async () => {
    const user = userEvent.setup()
    const onMarkRead = vi.fn()

    render(
      <NotificationCard notification={notification} onOpen={vi.fn()} onMarkRead={onMarkRead} actionsEnabled />,
    )

    expect(screen.getByText('Report ready')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Mark read' }))
    expect(onMarkRead).toHaveBeenCalled()
  })

  it('renders notification list', () => {
    render(<NotificationList notifications={[notification]} onOpen={vi.fn()} />)
    expect(screen.getByTestId('notification-list')).toBeInTheDocument()
  })
})

describe('notification center controls', () => {
  it('renders tabs with counts', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()

    render(
      <NotificationTabs
        activeTab="all"
        counts={{ all: 2, unread: 1, announcements: 0, mentions: 0, reminders: 0, workflow: 0, documents: 0, system: 1 }}
        onChange={onChange}
      />,
    )

    await user.click(screen.getByRole('tab', { name: /Unread \(1\)/ }))
    expect(onChange).toHaveBeenCalledWith('unread')
  })

  it('updates search and filters', async () => {
    const user = userEvent.setup()
    const onSearch = vi.fn()
    const onStatus = vi.fn()

    render(<NotificationSearch value="" onChange={onSearch} />)
    await user.type(screen.getByLabelText('Search notifications'), 'report')
    expect(onSearch).toHaveBeenCalled()

    render(
      <NotificationFilters statusFilter="" priorityFilter="" onStatusChange={onStatus} onPriorityChange={vi.fn()} />,
    )
    await user.selectOptions(screen.getByLabelText('Filter by status'), 'delivered')
    expect(onStatus).toHaveBeenCalledWith('delivered')
  })

  it('renders toolbar actions', async () => {
    const user = userEvent.setup()
    const onMarkAllRead = vi.fn().mockResolvedValue(undefined)

    render(<NotificationToolbar onRefresh={vi.fn()} onMarkAllRead={onMarkAllRead} actionsEnabled />)
    await user.click(screen.getByRole('button', { name: 'Mark all read' }))
    expect(onMarkAllRead).toHaveBeenCalled()
  })
})

describe('notification detail and drawer', () => {
  it('renders detail with related report metadata', () => {
    renderWithClient(<NotificationDetail notification={notification} actionsEnabled onMarkRead={vi.fn()} />)
    expect(screen.getByTestId('notification-detail')).toBeInTheDocument()
    expect(screen.getByText(/Report: summary/)).toBeInTheDocument()
  })

  it('renders drawer', () => {
    renderWithClient(
      <NotificationDrawer notification={notification} open onClose={vi.fn()} actionsEnabled />,
    )
    expect(screen.getByTestId('notification-drawer')).toBeInTheDocument()
  })

  it('approves mark read through API in detail flow', async () => {
    vi.spyOn(notificationsApi, 'markNotificationRead').mockResolvedValue({ ...notification, read_at: '2024-01-02' })
    const user = userEvent.setup()

    renderWithClient(
      <NotificationDetail
        notification={notification}
        actionsEnabled
        onMarkRead={async () => {
          await notificationsApi.markNotificationRead('n-1')
        }}
      />,
    )
    await user.click(screen.getByRole('button', { name: 'Mark read' }))

    await waitFor(() => {
      expect(notificationsApi.markNotificationRead).toHaveBeenCalledWith('n-1')
    })
  })
})
