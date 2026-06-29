import { beforeEach, describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { NotificationBell } from '@/components/shell/NotificationBell'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

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
  })

  it('shows unread count from hydrated runtime', () => {
    render(
      <HydratedRuntimeProvider>
        <NotificationBell />
      </HydratedRuntimeProvider>,
    )
    expect(screen.getByLabelText('Notifications, 4 unread')).toBeInTheDocument()
    expect(screen.getByText('4')).toBeInTheDocument()
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
              items: [{ item_key: 'home', label: 'Home', badge: 'New' }],
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
})
