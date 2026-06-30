import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { ActivityEntry } from '@/api/types/activity'
import { ActivitySeverityBadge } from '@/features/activity/components/ActivitySeverityBadge'
import { ActivityEntityBadge } from '@/features/activity/components/ActivityEntityBadge'
import { ActivityActorBadge } from '@/features/activity/components/ActivityActorBadge'
import { ActivityEmptyState } from '@/features/activity/components/ActivityEmptyState'
import { ActivityLoadingState } from '@/features/activity/components/ActivityLoadingState'
import { ActivityErrorState } from '@/features/activity/components/ActivityErrorState'
import { ActivitySearchBox } from '@/features/activity/components/ActivitySearchBox'
import { ActivityFilterPanel } from '@/features/activity/components/ActivityFilterPanel'
import { ActivityChangeSetViewer } from '@/features/activity/components/ActivityChangeSetViewer'
import { ActivityEntryCard } from '@/features/activity/components/ActivityEntryCard'
import { AuditEntryCard } from '@/features/activity/components/AuditEntryCard'
import { ActivityTimelineView } from '@/features/activity/components/ActivityTimeline'
import { ActivityFeed } from '@/features/activity/components/ActivityFeed'
import { AuditViewer } from '@/features/activity/components/AuditViewer'
import { ActivityDetailDrawer } from '@/features/activity/components/ActivityDetailDrawer'
import { ActivityCenterTabs } from '@/features/activity/components/ActivityCenterTabs'
import { ActivityCenterPage } from '@/features/activity/pages/ActivityCenterPage'
import { AuditViewerPage } from '@/features/activity/pages/AuditViewerPage'
import { EntityHistoryPage } from '@/features/activity/pages/EntityHistoryPage'
import { ActivityFeedWidget } from '@/features/activity/widgets/ActivityFeedWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as activityApi from '@/api/endpoints/activity'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>('@tanstack/react-router')
  return {
    ...actual,
    useNavigate: () => vi.fn(),
    useParams: () => ({ entityType: 'document', entityPublicId: 'doc-1' }),
  }
})

vi.mock('@/api/endpoints/activity', () => ({
  fetchActivityFeed: vi.fn(),
  safeFetchActivityFeed: vi.fn(),
  fetchAuditEvents: vi.fn(),
  fetchAuditSummary: vi.fn(),
  fetchEntityHistory: vi.fn(),
  labelActivitySource: (source: string) => source,
}))

const sampleEntry = (overrides: Partial<ActivityEntry> = {}): ActivityEntry => ({
  public_id: 'evt-1',
  action: 'document.updated',
  summary: 'Document updated',
  severity: 'info',
  entity: { type: 'document', public_id: 'doc-1', label: 'Invoice' },
  actor: { display_name: 'Alex' },
  occurred_at: '2024-01-01T00:00:00.000Z',
  source: 'backend',
  changes: [{ field: 'title', before: 'A', after: 'B' }],
  ...overrides,
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [],
    recent_items: [{ label: 'Docs', route: '/documents' }],
    shortcuts: [],
    quick_actions: [],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: { panel_position: 'top-right' },
    warnings: [],
    source: 'personalization_framework',
    runtime_context: {
      organization_public_id: null,
      workspace_public_id: null,
      membership_public_id: null,
      status: 'ok',
      missing_tables: [],
    },
  },
  navigationMenus: [],
  permissions: ['activity.read', 'audit.read', 'documents.read'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 1,
  warnings: [],
  source: 'heos_runtime',
}

function renderWithProviders(ui: React.ReactElement) {
  return render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('activity badges', () => {
  it('renders severity badge', () => {
    render(<ActivitySeverityBadge severity="critical" />)
    expect(screen.getByText('Critical')).toBeInTheDocument()
  })

  it('renders entity badge', () => {
    render(<ActivityEntityBadge type="document" label="Invoice" />)
    expect(screen.getByText('Invoice')).toBeInTheDocument()
  })

  it('renders actor badge', () => {
    render(<ActivityActorBadge actor={{ display_name: 'Alex' }} />)
    expect(screen.getByText('Alex')).toBeInTheDocument()
  })
})

describe('activity state components', () => {
  it('renders empty state', () => {
    render(<ActivityEmptyState />)
    expect(screen.getByTestId('activity-empty-state')).toBeInTheDocument()
  })

  it('renders loading state with aria-busy', () => {
    render(<ActivityLoadingState />)
    expect(screen.getByRole('status')).toHaveAttribute('aria-busy', 'true')
  })

  it('renders error state', () => {
    render(<ActivityErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
  })
})

describe('activity search and filters', () => {
  it('renders search box', () => {
    render(<ActivitySearchBox value="" onChange={() => undefined} />)
    expect(screen.getByLabelText('Search activity')).toBeInTheDocument()
  })

  it('renders filter panel controls', () => {
    render(<ActivityFilterPanel query={{ page: 1 }} onChange={() => undefined} />)
    expect(screen.getByLabelText('Entity type filter')).toBeInTheDocument()
    expect(screen.getByLabelText('Severity filter')).toBeInTheDocument()
  })
})

describe('activity change set viewer', () => {
  it('redacts sensitive values', () => {
    render(<ActivityChangeSetViewer changes={[{ field: 'password', before: 'old', after: 'new', sensitive: true }]} />)
    expect(screen.getAllByText('[redacted]')).toHaveLength(2)
  })

  it('shows empty message when no changes', () => {
    render(<ActivityChangeSetViewer changes={[]} />)
    expect(screen.getByText('No visible changes recorded.')).toBeInTheDocument()
  })
})

describe('activity cards and feed', () => {
  it('renders activity entry card', () => {
    render(<ActivityEntryCard entry={sampleEntry()} />)
    expect(screen.getByTestId('activity-entry-evt-1')).toBeInTheDocument()
  })

  it('renders audit entry card with expandable details', async () => {
    render(<AuditEntryCard entry={sampleEntry()} expanded onToggle={() => undefined} />)
    expect(screen.getByTestId('audit-entry-evt-1')).toBeInTheDocument()
    expect(screen.getByTestId('activity-change-set-viewer')).toBeInTheDocument()
  })

  it('renders activity feed', () => {
    render(<ActivityFeed items={[sampleEntry()]} />)
    expect(screen.getByTestId('activity-feed')).toBeInTheDocument()
  })

  it('renders audit viewer empty state', () => {
    render(<AuditViewer items={[]} />)
    expect(screen.getByText('No audit entries')).toBeInTheDocument()
  })
})

describe('activity timeline', () => {
  it('renders grouped timeline', () => {
    render(
      <ActivityTimelineView
        groups={[{ date: 'January 1, 2024', items: [sampleEntry()] }]}
      />,
    )
    expect(screen.getByTestId('activity-timeline')).toBeInTheDocument()
    expect(screen.getByTestId('activity-timeline-item-evt-1')).toBeInTheDocument()
  })
})

describe('activity detail drawer', () => {
  it('renders drawer when open', () => {
    render(<ActivityDetailDrawer entry={sampleEntry()} open onClose={() => undefined} />)
    expect(screen.getByRole('dialog', { name: /Activity detail/i })).toBeInTheDocument()
  })

  it('returns null when closed', () => {
    const { container } = render(<ActivityDetailDrawer entry={sampleEntry()} open={false} onClose={() => undefined} />)
    expect(container).toBeEmptyDOMElement()
  })
})

describe('activity center tabs', () => {
  it('renders tabs with selection', () => {
    render(<ActivityCenterTabs tabs={[{ key: 'recent', label: 'Recent' }]} activeTab="recent" onChange={() => undefined} />)
    expect(screen.getByRole('tab', { name: 'Recent' })).toHaveAttribute('aria-selected', 'true')
  })
})

describe('activity pages', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(activityApi.safeFetchActivityFeed).mockResolvedValue({
      items: [sampleEntry()],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
      source: 'backend',
    })
    vi.mocked(activityApi.fetchAuditEvents).mockResolvedValue({
      items: [sampleEntry()],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
      source: 'backend',
    })
    vi.mocked(activityApi.fetchAuditSummary).mockResolvedValue({ total_events: 1 })
    vi.mocked(activityApi.fetchEntityHistory).mockResolvedValue({
      items: [sampleEntry()],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
      source: 'backend',
    })
  })

  it('renders activity center page', async () => {
    renderWithProviders(<ActivityCenterPage />)
    expect(screen.getByTestId('activity-center-page')).toBeInTheDocument()
    await waitFor(() => {
      expect(screen.getByTestId('activity-feed')).toBeInTheDocument()
    })
  })

  it('renders audit viewer page', async () => {
    renderWithProviders(<AuditViewerPage />)
    expect(screen.getByTestId('audit-viewer-page')).toBeInTheDocument()
  })

  it('renders entity history page', async () => {
    renderWithProviders(<EntityHistoryPage />)
    expect(screen.getByTestId('entity-history-page')).toBeInTheDocument()
  })

  it('renders activity feed widget', async () => {
    renderWithProviders(<ActivityFeedWidget />)
    expect(screen.getByTestId('activity-feed-widget')).toBeInTheDocument()
  })
})

describe('activity binding renderer', () => {
  it('renders feed binding mode', async () => {
    const { ActivityBindingRenderer } = await import('@/features/renderer/bindings/ActivityBindingRenderer')
    renderWithProviders(
      <ActivityBindingRenderer
        component={{
          public_id: 'activity-1',
          component_key: 'activity-1',
          component_type: 'activity_feed',
          name: 'Activity',
          binding_config: { mode: 'feed', per_page: 5 },
        }}
      />,
    )
    expect(screen.getByTestId('activity-binding-renderer')).toBeInTheDocument()
  })
})

describe('activity center interactions', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(activityApi.safeFetchActivityFeed).mockResolvedValue({
      items: [sampleEntry()],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
      source: 'backend',
    })
    vi.mocked(activityApi.fetchAuditEvents).mockResolvedValue({
      items: [sampleEntry()],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
      source: 'backend',
    })
  })

  it('opens detail drawer from feed card', async () => {
    const { ActivityCenter } = await import('@/features/activity/components/ActivityCenter')
    renderWithProviders(<ActivityCenter />)
    await waitFor(() => expect(screen.getByTestId('activity-entry-evt-1')).toBeInTheDocument())
    await userEvent.click(screen.getByTestId('activity-entry-evt-1'))
    expect(screen.getByTestId('activity-detail-drawer')).toBeInTheDocument()
  })

  it('switches to audit tab', async () => {
    const { ActivityCenter } = await import('@/features/activity/components/ActivityCenter')
    renderWithProviders(<ActivityCenter />)
    await userEvent.click(screen.getByRole('tab', { name: 'Audit log' }))
    await waitFor(() => expect(screen.getByTestId('audit-viewer')).toBeInTheDocument())
  })
})
