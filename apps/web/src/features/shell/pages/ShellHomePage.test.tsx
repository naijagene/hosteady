import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ShellHomePage } from '@/features/shell/pages/ShellHomePage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as uiApi from '@/api/endpoints/ui'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: {
    organization: {
      public_id: 'org-1',
      name: 'Acme Org',
      slug: 'acme',
      status: 'active',
      organization_code: 'acme',
      membership: {
        public_id: 'mem-1',
        status: 'active',
        join_method: 'invite',
        default_workspace_public_id: 'ws-1',
      },
    },
    workspace: {
      public_id: 'ws-1',
      name: 'Main Workspace',
      slug: 'main',
      is_default: true,
      status: 'active',
    },
    membership: {
      public_id: 'mem-1',
      status: 'active',
      join_method: 'invite',
      default_workspace_public_id: 'ws-1',
    },
    active_applications: [{ public_id: 'app-1' }, { public_id: 'app-2' }],
    active_application: null,
    runtime_version: 1,
    settings_version: 1,
    runtime_metadata: {},
    capabilities: {},
    navigation: {},
    feature_flags: {},
    module_diagnostics: {},
    settings_metadata: {},
  },
  themeRuntime: { source: 'tenant-theme' } as HydratedRuntimeBundle['themeRuntime'],
  personalizationRuntime: {
    preferences: [],
    favorites: [{ label: 'Settings' }],
    recent_items: [{ label: 'Users table' }],
    shortcuts: [{ label: 'Open settings' }],
    quick_actions: [{ label: 'Create record' }],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: {},
    warnings: [],
    source: 'personalization-runtime',
    runtime_context: {
      organization_public_id: 'org-1',
      workspace_public_id: 'ws-1',
      membership_public_id: 'mem-1',
      status: 'ready',
      missing_tables: [],
    },
  },
  navigationMenus: [
    {
      menu_key: 'main',
      label: 'Main',
      groups: [{ group_key: 'core', label: 'Core', items: [{ item_key: 'home', label: 'Home' }] }],
      metadata: {},
    },
  ],
  permissions: ['settings.read'],
  roles: [],
  user: {
    public_id: 'user-1',
    display_name: 'Ada',
    email: 'ada@test.com',
    status: 'active',
  },
  organization: {
    public_id: 'org-1',
    name: 'Acme Org',
    slug: 'acme',
    status: 'active',
    organization_code: 'acme',
    membership: {
      public_id: 'mem-1',
      status: 'active',
      join_method: 'invite',
      default_workspace_public_id: 'ws-1',
    },
  },
  workspace: {
    public_id: 'ws-1',
    name: 'Main Workspace',
    slug: 'main',
    is_default: true,
    status: 'active',
  },
  membership: {
    public_id: 'mem-1',
    status: 'active',
    join_method: 'invite',
    default_workspace_public_id: 'ws-1',
  },
  application: null,
  unreadNotificationCount: 3,
  warnings: [],
  source: 'runtime',
}

function renderHome() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>
        <ShellHomePage />
      </HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('ShellHomePage', () => {
  it('renders welcome and runtime summary widgets', () => {
    vi.spyOn(uiApi, 'fetchUiPages').mockResolvedValue([
      { public_id: '1', page_key: 'home', name: 'Home', module_key: 'platform' },
    ] as never)

    renderHome()

    expect(screen.getByText(/Welcome back, Ada/)).toBeInTheDocument()
    expect(screen.getByText('Acme Org')).toBeInTheDocument()
    expect(screen.getByText('Main Workspace')).toBeInTheDocument()
    expect(screen.getByText('Hydrated runtime loaded')).toBeInTheDocument()
    expect(screen.getByText('personalization-runtime')).toBeInTheDocument()
    expect(screen.getByLabelText('Applications metric')).toHaveTextContent('2')
    expect(screen.getByLabelText('Notifications metric')).toHaveTextContent('3')
  })

  it('renders personalization-driven home widgets', () => {
    vi.spyOn(uiApi, 'fetchUiPages').mockResolvedValue([])
    renderHome()

    expect(screen.getByText('Settings')).toBeInTheDocument()
    expect(screen.getByText('Users table')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Open settings' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Create record' })).toBeInTheDocument()
  })
})
