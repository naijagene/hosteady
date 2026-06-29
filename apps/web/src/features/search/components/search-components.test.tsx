import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { SearchResult } from '@/api/types/search'
import { SearchInput } from '@/features/search/components/SearchInput'
import { SearchResultItem } from '@/features/search/components/SearchResultItem'
import { SearchResultGroup } from '@/features/search/components/SearchResultGroup'
import { SearchResultsList } from '@/features/search/components/SearchResultsList'
import { SearchEmptyState } from '@/features/search/components/SearchEmptyState'
import { SearchLoadingState } from '@/features/search/components/SearchLoadingState'
import { SearchErrorState } from '@/features/search/components/SearchErrorState'
import { SearchFilters } from '@/features/search/components/SearchFilters'
import { RecentSearches } from '@/features/search/components/RecentSearches'
import { UniversalFinder } from '@/features/search/components/UniversalFinder'
import { CommandPalette } from '@/features/search/components/CommandPalette'
import { GlobalSearchDialog } from '@/features/search/components/GlobalSearchDialog'
import { SearchPage } from '@/features/search/pages/SearchPage'
import { SearchWidget } from '@/features/search/widgets/SearchWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as searchApi from '@/api/endpoints/search'

const navigateMock = vi.fn()

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>('@tanstack/react-router')
  return {
    ...actual,
    useNavigate: () => navigateMock,
    useRouterState: (options?: { select?: (state: { location: { pathname: string } }) => unknown }) => {
      const state = { location: { pathname: '/search' } }
      return options?.select ? options.select(state) : state
    },
    Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
  }
})

vi.mock('@/api/endpoints/search', () => ({
  searchTenant: vi.fn(),
  fetchSearchSuggestions: vi.fn().mockResolvedValue([]),
}))

const sampleResult = (overrides: Partial<SearchResult> = {}): SearchResult => ({
  id: 'result-1',
  title: 'Documents',
  description: 'Document manager',
  type: 'document',
  icon: 'document',
  route: '/documents',
  source: 'local',
  ...overrides,
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [{ label: 'Favorite Page', route: '/documents' }],
    recent_items: [{ label: 'Recent Page', route: '/workflows' }],
    shortcuts: [{ label: 'Reports Shortcut', route: '/reports/platform/summary' }],
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
  permissions: ['documents.read', 'reports.read', 'dashboards.read', 'workflow.runtime.read', 'notifications.read'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
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

describe('SearchInput', () => {
  it('renders searchbox with label', () => {
    render(<SearchInput value="" onChange={() => undefined} />)
    expect(screen.getByRole('combobox', { name: 'Search HEOS' })).toBeInTheDocument()
  })

  it('calls onChange when typing', async () => {
    const onChange = vi.fn()
    render(<SearchInput value="" onChange={onChange} />)
    await userEvent.type(screen.getByTestId('search-input'), 'doc')
    expect(onChange).toHaveBeenCalled()
  })
})

describe('SearchResultItem', () => {
  it('renders title and description', () => {
    render(<SearchResultItem result={sampleResult()} />)
    expect(screen.getByText('Documents')).toBeInTheDocument()
  })

  it('marks active result', () => {
    render(<SearchResultItem result={sampleResult()} active />)
    expect(screen.getByTestId('search-result-result-1')).toHaveAttribute('data-active', 'true')
  })

  it('calls onSelect when clicked', async () => {
    const onSelect = vi.fn()
    render(<SearchResultItem result={sampleResult()} onSelect={onSelect} />)
    await userEvent.click(screen.getByTestId('search-result-result-1'))
    expect(onSelect).toHaveBeenCalledWith(sampleResult())
  })
})

describe('SearchResultGroup', () => {
  it('renders grouped results', () => {
    render(<SearchResultGroup label="Documents" results={[sampleResult()]} />)
    expect(screen.getByRole('group', { name: 'Documents' })).toBeInTheDocument()
    expect(screen.getByTestId('search-result-result-1')).toBeInTheDocument()
  })

  it('returns null for empty group', () => {
    const { container } = render(<SearchResultGroup label="Empty" results={[]} />)
    expect(container).toBeEmptyDOMElement()
  })
})

describe('SearchResultsList', () => {
  it('renders grouped listbox', () => {
    render(
      <SearchResultsList
        results={[sampleResult(), sampleResult({ id: 'cmd-1', type: 'command', source: 'command', title: 'Go Home' })]}
      />,
    )
    expect(screen.getByRole('listbox', { name: 'Search results' })).toBeInTheDocument()
  })

  it('renders flat list when grouped=false', () => {
    render(<SearchResultsList results={[sampleResult()]} grouped={false} />)
    expect(screen.getByTestId('search-result-result-1')).toBeInTheDocument()
  })
})

describe('search state components', () => {
  it('renders empty state with query', () => {
    render(<SearchEmptyState query="missing" />)
    expect(screen.getByText(/No results for/)).toBeInTheDocument()
  })

  it('renders loading state', () => {
    render(<SearchLoadingState />)
    expect(screen.getByText('Searching…')).toBeInTheDocument()
  })

  it('renders error state', () => {
    render(<SearchErrorState message="Failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Failed')
  })
})

describe('SearchFilters', () => {
  it('renders filter chips', () => {
    render(<SearchFilters />)
    expect(screen.getByText('All')).toBeInTheDocument()
    expect(screen.getByText('Documents')).toBeInTheDocument()
  })

  it('calls onChange when filter selected', async () => {
    const onChange = vi.fn()
    render(<SearchFilters onChange={onChange} />)
    await userEvent.click(screen.getByText('Documents'))
    expect(onChange).toHaveBeenCalledWith('document')
  })
})

describe('RecentSearches', () => {
  it('renders recent query chips', () => {
    render(<RecentSearches items={['docs', 'reports']} />)
    expect(screen.getByText('docs')).toBeInTheDocument()
  })
})

describe('UniversalFinder', () => {
  it('renders results and live region count', () => {
    render(<UniversalFinder query="doc" results={[sampleResult()]} />)
    expect(screen.getByText('1 results', { selector: '.sr-only' })).toBeInTheDocument()
  })

  it('shows empty state for missing query results', () => {
    render(<UniversalFinder query="missing" results={[]} />)
    expect(screen.getByTestId('search-empty-state')).toBeInTheDocument()
  })
})

describe('CommandPalette', () => {
  it('renders dialog when open', () => {
    render(
      <CommandPalette
        palette={{
          open: true,
          setOpen: vi.fn(),
          query: '',
          setQuery: vi.fn(),
          activeIndex: 0,
          flatResults: [sampleResult()],
          isLoading: false,
          error: null,
          source: 'runtime',
          activateResult: vi.fn(),
        }}
        recentSearches={['docs']}
      />,
    )
    expect(screen.getByRole('dialog', { name: 'Command palette' })).toBeInTheDocument()
  })
})

describe('GlobalSearchDialog', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(searchApi.searchTenant).mockResolvedValue({ query: '', items: [], total: 0, source: 'runtime' })
  })

  it('renders trigger with shortcut hint', () => {
    renderWithProviders(<GlobalSearchDialog />)
    expect(screen.getByTestId('global-search-trigger')).toBeInTheDocument()
  })

  it('opens palette on trigger click', async () => {
    renderWithProviders(<GlobalSearchDialog />)
    await userEvent.click(screen.getByTestId('global-search-trigger'))
    expect(screen.getByTestId('command-palette')).toBeInTheDocument()
  })

  it('opens palette with Ctrl+K', async () => {
    renderWithProviders(<GlobalSearchDialog />)
    fireEvent.keyDown(window, { key: 'k', ctrlKey: true })
    expect(await screen.findByTestId('command-palette')).toBeInTheDocument()
  })

  it('closes palette with Escape', async () => {
    renderWithProviders(<GlobalSearchDialog />)
    await userEvent.click(screen.getByTestId('global-search-trigger'))
    fireEvent.keyDown(window, { key: 'Escape' })
    await waitFor(() => {
      expect(screen.queryByTestId('command-palette')).not.toBeInTheDocument()
    })
  })
})

describe('SearchPage', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(searchApi.searchTenant).mockResolvedValue({ query: 'doc', items: [sampleResult()], total: 1, source: 'runtime' })
  })

  it('renders full-page search view', () => {
    renderWithProviders(<SearchPage />)
    expect(screen.getByTestId('search-page')).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Search' })).toBeInTheDocument()
  })

  it('shows results after typing', async () => {
    renderWithProviders(<SearchPage />)
    await userEvent.type(screen.getByTestId('search-input'), 'doc')
    await waitFor(() => {
      expect(screen.getByTestId('search-result-result-1')).toBeInTheDocument()
    })
  })
})

describe('SearchWidget', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(searchApi.searchTenant).mockResolvedValue({ query: '', items: [], total: 0, source: 'runtime' })
  })

  it('renders compact search widget', () => {
    renderWithProviders(<SearchWidget />)
    expect(screen.getByTestId('search-widget')).toBeInTheDocument()
  })
})

describe('SearchDialog shell export', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(searchApi.searchTenant).mockResolvedValue({ query: '', items: [], total: 0, source: 'runtime' })
  })

  it('shell SearchDialog re-exports global search', async () => {
    const { SearchDialog } = await import('@/components/shell/SearchDialog')
    renderWithProviders(<SearchDialog />)
    expect(screen.getByTestId('global-search-trigger')).toBeInTheDocument()
  })
})
