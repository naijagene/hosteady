import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { RuntimeStatusWidget } from '@/features/admin/widgets/RuntimeStatusWidget'
import { FeatureSummaryWidget } from '@/features/admin/widgets/FeatureSummaryWidget'
import { OrganizationSummaryWidget } from '@/features/admin/widgets/OrganizationSummaryWidget'
import { WorkspaceStatusWidget } from '@/features/admin/widgets/WorkspaceStatusWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as adminApi from '@/api/endpoints/admin'
import * as uiApi from '@/api/endpoints/ui'

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
    workspace: { public_id: 'ws-1', name: 'Main Workspace', slug: 'main', is_default: true, status: 'active' },
    membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' },
    active_applications: [{ public_id: 'app-1', name: 'Platform' }, { public_id: 'app-2', name: 'Docs' }],
    active_application: null,
    runtime_version: 2,
    settings_version: 1,
    runtime_metadata: {},
    capabilities: { forms: 3, tables: 2 },
    navigation: [],
    feature_flags: { demo_preview: true },
    module_diagnostics: {},
    settings_metadata: { timezone: 'UTC' },
  },
  themeRuntime: { definition: {}, version: {}, brand_profile: {}, theme: {}, runtime_context: {}, permissions: {}, warnings: [], source: 'themes' },
  personalizationRuntime: {
    preferences: [],
    favorites: [{}],
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
  navigationMenus: [{ menu_key: 'main', label: 'Main', groups: [], metadata: {} }],
  permissions: ['platform.read'],
  roles: ['admin'],
  user: { public_id: 'u-1', display_name: 'Alex', email: 'alex@example.com', status: 'active' },
  organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
  workspace: { public_id: 'ws-1', name: 'Main Workspace', slug: 'main', is_default: true, status: 'active' },
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

describe('admin dashboard widgets', () => {
  beforeEach(() => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.mocked(adminApi.fetchWorkspaceRuntimeHealth).mockResolvedValue({ status: 'healthy', source: 'backend' })
    vi.mocked(adminApi.safeFetchTenantApplications).mockResolvedValue([])
    vi.mocked(uiApi.fetchUiPages).mockResolvedValue([])
  })

  it('renders runtime status widget', async () => {
    renderWithProviders(<RuntimeStatusWidget />)
    await waitFor(() => expect(screen.getByTestId('runtime-status-widget')).toBeInTheDocument())
  })

  it('renders feature summary widget', async () => {
    renderWithProviders(<FeatureSummaryWidget />)
    await waitFor(() => expect(screen.getByTestId('feature-summary-widget')).toBeInTheDocument())
  })

  it('renders organization summary widget', async () => {
    renderWithProviders(<OrganizationSummaryWidget />)
    await waitFor(() => expect(screen.getByTestId('organization-summary-widget')).toBeInTheDocument())
    expect(screen.getByText('Acme')).toBeInTheDocument()
  })

  it('renders workspace status widget', () => {
    renderWithProviders(<WorkspaceStatusWidget />)
    expect(screen.getByTestId('workspace-status-widget')).toHaveTextContent('Main Workspace')
  })
})
