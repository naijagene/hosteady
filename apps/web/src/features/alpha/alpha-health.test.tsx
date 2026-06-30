import { describe, expect, it, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import {
  buildAlphaApiCheck,
  buildAlphaFeatureChecks,
  buildAlphaHealthSnapshot,
  buildAlphaRuntimeChecks,
  deriveAlphaHealthStatus,
} from './core/alpha-health'
import { AlphaHealthPage } from './components/AlphaHealthPage'
import { AlphaReadinessWidget } from './components/AlphaReadinessWidget'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>('@tanstack/react-router')
  return {
    ...actual,
    Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
  }
})

const runtime = {
  tenantContext: null,
  workspaceRuntime: {
    organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
    workspace: { public_id: 'ws-1', name: 'Main', slug: 'main', is_default: true, status: 'active' },
    membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' },
    active_applications: [],
    active_application: null,
    runtime_version: 1,
    settings_version: 1,
    runtime_metadata: {},
    capabilities: {},
    navigation: [],
    feature_flags: {},
    module_diagnostics: {},
    settings_metadata: {},
  },
  themeRuntime: { source: 'themes' },
  personalizationRuntime: {
    favorites: [],
    recent_items: [],
    shortcuts: [],
    quick_actions: [],
    runtime_context: { missing_tables: [] },
    source: 'personalization',
  },
  navigationMenus: [{ menu_key: 'main', label: 'Main', groups: [{ group_key: 'core', label: 'Core', items: [{ item_key: 'home', label: 'Home' }] }], metadata: {} }],
  permissions: ['platform.read'],
  roles: ['admin'],
  user: { public_id: 'u-1', display_name: 'Alex', email: 'alex@example.com', status: 'active' },
  organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
  workspace: { public_id: 'ws-1', name: 'Main', slug: 'main', is_default: true, status: 'active' },
  membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' },
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'heos_runtime',
} as unknown as HydratedRuntimeBundle

function renderWithProviders(ui: React.ReactElement) {
  return render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('alpha health core', () => {
  it('builds runtime checks for hydrated session', () => {
    const checks = buildAlphaRuntimeChecks({
      authenticated: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      runtime,
    })
    expect(checks.every((item) => item.status !== 'unavailable')).toBe(true)
  })

  it('accepts alpha navigation payloads without group_key', () => {
    const alphaRuntime = {
      ...runtime,
      navigationMenus: [
        {
          menu_key: 'alpha-preview',
          label: 'Alpha Primary Navigation',
          groups: [
            {
              label: 'Main',
              items: [{ item_key: 'alpha-home', label: 'Alpha Preview Home' }],
            },
          ],
          metadata: {},
        },
      ],
    } as HydratedRuntimeBundle

    const checks = buildAlphaRuntimeChecks({
      authenticated: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      runtime: alphaRuntime,
    })

    expect(checks.find((item) => item.key === 'navigation')?.status).toBe('ready')
  })

  it('marks unavailable when runtime missing', () => {
    const checks = buildAlphaRuntimeChecks({
      authenticated: false,
      organizationPublicId: null,
      workspacePublicId: null,
      runtime: null,
    })
    expect(deriveAlphaHealthStatus(checks)).toBe('unavailable')
  })

  it('marks warning when personalization has warnings', () => {
    const checks = buildAlphaRuntimeChecks({
      authenticated: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      runtime: { ...runtime, warnings: ['Missing tables'] },
    })
    expect(deriveAlphaHealthStatus(checks)).toBe('warning')
  })

  it('builds feature checks for all platform modules', () => {
    const features = buildAlphaFeatureChecks({ ...runtime, permissions: [] }, [])
    expect(features).toHaveLength(10)
    expect(features.every((feature) => feature.available)).toBe(true)
  })

  it('marks features unavailable without runtime', () => {
    const features = buildAlphaFeatureChecks(null, [])
    expect(features.every((feature) => !feature.available)).toBe(true)
  })

  it('builds api diagnostics snapshot', () => {
    const api = buildAlphaApiCheck({
      authenticated: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      runtimeEndpointStatus: 'hydrated',
    })
    expect(api.base_url).toContain('/api/v1')
    expect(api.token_present).toBe(true)
    expect(api.tenant_headers_present).toBe(true)
  })

  it('builds full alpha health snapshot', () => {
    const snapshot = buildAlphaHealthSnapshot({
      authenticated: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      runtime: { ...runtime, permissions: [] },
      runtimeEndpointStatus: 'hydrated',
    })
    expect(snapshot.status).toBe('ready')
    expect(snapshot.runtime).toHaveLength(8)
    expect(snapshot.features).toHaveLength(10)
    expect(snapshot.features.every((feature) => feature.available)).toBe(true)
  })
})

describe('alpha health components', () => {
  beforeEach(() => {
    useAuthStore.setState({
      accessToken: 'token',
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
    })
    useAuthStore.getState().setHydratedRuntime(runtime)
  })

  it('renders alpha health page', () => {
    renderWithProviders(<AlphaHealthPage />)
    expect(screen.getByTestId('alpha-health-page')).toBeInTheDocument()
    expect(screen.getByTestId('alpha-runtime-checklist')).toBeInTheDocument()
    expect(screen.getByTestId('alpha-feature-checklist')).toBeInTheDocument()
  })

  it('renders alpha readiness widget with links', () => {
    renderWithProviders(<AlphaReadinessWidget />)
    expect(screen.getByTestId('alpha-readiness-widget')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Alpha health' })).toHaveAttribute('href', '/alpha/health')
    expect(screen.getByRole('link', { name: 'Admin console' })).toHaveAttribute('href', '/admin')
  })
})
