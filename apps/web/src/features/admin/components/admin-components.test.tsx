import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AdminStatusBadge } from '@/features/admin/components/AdminStatusBadge'
import { AdminDefinitionList } from '@/features/admin/components/AdminDefinitionList'
import { AdminNav } from '@/features/admin/components/AdminNav'
import { PlatformOverviewPanel } from '@/features/admin/components/PlatformOverviewPanel'
import { PermissionBrowserPanel } from '@/features/admin/components/PermissionBrowserPanel'
import { RuntimeDiagnosticsPanel } from '@/features/admin/components/RuntimeDiagnosticsPanel'
import { AdminOverviewPage } from '@/features/admin/pages/AdminOverviewPage'
import { AdminPermissionsPage } from '@/features/admin/pages/AdminPermissionsPage'
import { PlatformStatusWidget } from '@/features/admin/widgets/PlatformStatusWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as adminApi from '@/api/endpoints/admin'
import * as uiApi from '@/api/endpoints/ui'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>('@tanstack/react-router')
  return {
    ...actual,
    useRouterState: (options?: { select?: (state: { location: { pathname: string } }) => unknown }) => {
      const state = { location: { pathname: '/admin' } }
      return options?.select ? options.select(state) : state
    },
    Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
  }
})

vi.mock('@/api/endpoints/admin', () => ({
  fetchWorkspaceRuntimeHealth: vi.fn(),
  safeFetchTenantApplications: vi.fn(),
}))

vi.mock('@/api/endpoints/ui', () => ({
  fetchUiPages: vi.fn(),
}))

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: {
    organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
    workspace: { public_id: 'ws-1', name: 'Main', slug: 'main', is_default: true, status: 'active' },
    membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' },
    active_applications: [{ public_id: 'app-1', name: 'Platform' }],
    active_application: null,
    runtime_version: 1,
    settings_version: 1,
    runtime_metadata: {},
    capabilities: {},
    navigation: [],
    feature_flags: {},
    module_diagnostics: {},
    settings_metadata: { timezone: 'UTC' },
  },
  themeRuntime: { definition: {}, version: {}, brand_profile: {}, theme: {}, runtime_context: {}, permissions: {}, warnings: [], source: 'themes' },
  personalizationRuntime: {
    preferences: [],
    favorites: [],
    recent_items: [],
    shortcuts: [],
    quick_actions: [],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: { panel_position: 'top-right' },
    warnings: [],
    source: 'personalization',
    runtime_context: { organization_public_id: 'org-1', workspace_public_id: 'ws-1', membership_public_id: 'm-1', status: 'ok', missing_tables: [] },
  },
  navigationMenus: [],
  permissions: ['platform.read', 'documents.read', 'permissions.read'],
  roles: ['admin'],
  user: { public_id: 'u-1', display_name: 'Alex', email: 'alex@example.com', status: 'active' },
  organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
  workspace: { public_id: 'ws-1', name: 'Main', slug: 'main', is_default: true, status: 'active' },
  membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' },
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

describe('admin components', () => {
  it('renders status badge', () => {
    render(<AdminStatusBadge status="healthy" />)
    expect(screen.getByText('healthy')).toBeInTheDocument()
  })

  it('renders definition list', () => {
    render(<AdminDefinitionList items={[{ label: 'Environment', value: 'test' }]} />)
    expect(screen.getByText('Environment')).toBeInTheDocument()
  })

  it('renders admin nav with aria label', () => {
    render(<AdminNav permissions={['platform.read', 'permissions.read']} />)
    expect(screen.getByLabelText('Administration sections')).toBeInTheDocument()
  })

  it('renders platform overview panel', () => {
    render(<PlatformOverviewPanel info={{ heos_version: 'HEOS Platform v1.0', frontend_version: '0.0.0', feature_counts: { applications: 1 } }} />)
    expect(screen.getByText('Platform Overview')).toBeInTheDocument()
  })

  it('renders permission browser with search', () => {
    render(<PermissionBrowserPanel permissions={['documents.read', 'platform.read']} />)
    expect(screen.getByLabelText('Search permissions')).toBeInTheDocument()
  })

  it('renders runtime diagnostics list', () => {
    render(<RuntimeDiagnosticsPanel diagnostics={[{ key: 'runtime', label: 'Runtime loaded', status: 'loaded', detail: 'Loaded' }]} />)
    expect(screen.getByTestId('runtime-diagnostics')).toBeInTheDocument()
  })
})

describe('admin pages', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(adminApi.fetchWorkspaceRuntimeHealth).mockResolvedValue({ status: 'healthy', source: 'backend' })
    vi.mocked(adminApi.safeFetchTenantApplications).mockResolvedValue([])
    vi.mocked(uiApi.fetchUiPages).mockResolvedValue([])
  })

  it('renders admin overview page', async () => {
    renderWithProviders(<AdminOverviewPage />)
    expect(screen.getByTestId('admin-console-layout')).toBeInTheDocument()
    await waitFor(() => expect(screen.getByTestId('admin-section-platform-overview')).toBeInTheDocument())
  })

  it('renders admin permissions page', async () => {
    renderWithProviders(<AdminPermissionsPage />)
    expect(screen.getByTestId('permission-browser')).toBeInTheDocument()
  })

  it('filters permissions via search', async () => {
    renderWithProviders(<AdminPermissionsPage />)
    await userEvent.type(screen.getByLabelText('Search permissions'), 'documents')
    expect(screen.getByText('documents.read')).toBeInTheDocument()
  })

  it('renders platform status widget', async () => {
    renderWithProviders(<PlatformStatusWidget />)
    expect(screen.getByTestId('platform-status-widget')).toBeInTheDocument()
  })
})

describe('administration binding renderer', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(adminApi.fetchWorkspaceRuntimeHealth).mockResolvedValue(null)
    vi.mocked(adminApi.safeFetchTenantApplications).mockResolvedValue([])
    vi.mocked(uiApi.fetchUiPages).mockResolvedValue([])
  })

  it('renders platform overview binding', async () => {
    const { AdministrationBindingRenderer } = await import('@/features/renderer/bindings/AdministrationBindingRenderer')
    renderWithProviders(
      <AdministrationBindingRenderer
        component={{
          public_id: 'admin-1',
          component_key: 'admin-1',
          component_type: 'platform_overview',
          name: 'Platform',
          binding_config: { mode: 'platform_overview' },
        }}
      />,
    )
    expect(screen.getByTestId('administration-binding-renderer')).toBeInTheDocument()
  })
})
