import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { NotificationBell } from '@/components/shell/NotificationBell'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as notificationsApi from '@/api/endpoints/notifications'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual<typeof import('@tanstack/react-router')>(
    '@tanstack/react-router',
  )

  return {
    ...actual,
    useRouterState: (options?: {
      select?: (state: { location: { pathname: string } }) => unknown
    }) => {
      const state = { location: { pathname: '/' } }
      return options?.select ? options.select(state) : state
    },
    useNavigate: () => vi.fn(),
    Link: ({ children, to }: { children: React.ReactNode; to: string }) => <a href={to}>{children}</a>,
  }
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
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
  permissions: [],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 4,
  warnings: [],
  source: 'heos_runtime',
}

describe('NotificationBell', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    useAuthStore.getState().setHydratedRuntime(runtime)
    vi.spyOn(notificationsApi, 'fetchNotifications').mockImplementation(
      () => new Promise(() => undefined),
    )
  })

  it('shows unread count from hydrated runtime', () => {
    render(
      <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
        <HydratedRuntimeProvider>
          <NotificationBell />
        </HydratedRuntimeProvider>
      </QueryClientProvider>,
    )
    expect(screen.getByLabelText('Notifications, 4 unread')).toBeInTheDocument()
  })
})

describe('RuntimeLoader', () => {
  it('renders children for unauthenticated routes', async () => {
    const { RuntimeLoader } = await import('@/features/runtime/RuntimeLoader')

    render(
      <RuntimeLoader>
        <div>Public content</div>
      </RuntimeLoader>,
    )

    expect(screen.getByText('Public content')).toBeInTheDocument()
  })
})

describe('AppSidebar', () => {
  it('renders runtime navigation groups', async () => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    const { NavigationProvider } = await import('@/app/providers/NavigationProvider')
    const { HydratedRuntimeProvider } = await import(
      '@/features/runtime/HydratedRuntimeProvider'
    )
    const { AppSidebar } = await import('@/components/navigation/AppSidebar')

    useAuthStore.getState().setHydratedRuntime({
      ...runtime,
      navigationMenus: [
        {
          menu_key: 'main',
          label: 'Main',
          groups: [
            {
              group_key: 'core',
              label: 'Core',
              items: [
                {
                  item_key: 'home',
                  label: 'Home',
                  badge: 'New',
                  route: { module_key: 'platform', page_key: 'home' },
                },
              ],
            },
          ],
          metadata: {},
        },
      ],
    })

    render(
      <HydratedRuntimeProvider>
        <NavigationProvider>
          <AppSidebar />
        </NavigationProvider>
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByText('Core')).toBeInTheDocument()
    expect(screen.getByText('Home')).toBeInTheDocument()
    expect(screen.getByText('New')).toBeInTheDocument()
  })

  it('renders alpha navigation items from backend payload', async () => {
    const { NavigationProvider } = await import('@/app/providers/NavigationProvider')
    const { HydratedRuntimeProvider } = await import(
      '@/features/runtime/HydratedRuntimeProvider'
    )
    const { AppSidebar } = await import('@/components/navigation/AppSidebar')

    useAuthStore.getState().setHydratedRuntime({
      ...runtime,
      navigationMenus: [
        {
          menu_key: 'alpha-primary',
          label: 'Alpha Primary Navigation',
          groups: [
            {
              group_key: 'default',
              label: 'Main',
              items: [
                {
                  item_key: 'alpha-home',
                  label: 'Alpha Preview Home',
                  route: { module_key: 'alpha.preview', page_key: 'home' },
                },
                {
                  item_key: 'alpha-documents',
                  label: 'Documents',
                  route: { path: '/documents' },
                },
              ],
            },
          ],
          metadata: {},
        },
      ],
    })

    render(
      <HydratedRuntimeProvider>
        <NavigationProvider>
          <AppSidebar />
        </NavigationProvider>
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByText('Alpha Preview Home')).toBeInTheDocument()
    expect(screen.getByText('Documents')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Alpha Preview Home' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Documents' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Alpha Preview Home' })).not.toBeInTheDocument()
    expect(screen.queryByText('Runtime fallback routes')).not.toBeInTheDocument()
  })

  it('renders clickable alpha dashboard navigation links', async () => {
    useAuthStore.getState().setHydratedRuntime({
      ...runtime,
      navigationMenus: [
        {
          menu_key: 'alpha-primary',
          label: 'Alpha Primary Navigation',
          groups: [
            {
              group_key: 'default',
              label: 'Main',
              items: [
                {
                  item_key: 'alpha-dashboard',
                  label: 'Alpha Dashboard',
                  route: { path: '/dashboards/alpha.preview/sample', module_key: 'alpha.preview' },
                },
              ],
            },
          ],
          metadata: {},
        },
      ],
    })

    const { NavigationProvider } = await import('@/app/providers/NavigationProvider')
    const { HydratedRuntimeProvider } = await import(
      '@/features/runtime/HydratedRuntimeProvider'
    )
    const { AppSidebar } = await import('@/components/navigation/AppSidebar')

    render(
      <HydratedRuntimeProvider>
        <NavigationProvider>
          <AppSidebar />
        </NavigationProvider>
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByRole('link', { name: 'Alpha Dashboard' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Alpha Dashboard' })).not.toBeInTheDocument()
  })
})
