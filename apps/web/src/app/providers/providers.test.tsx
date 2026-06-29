import { beforeEach, describe, expect, it } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { NavigationProvider } from '@/app/providers/NavigationProvider'
import { ThemeProvider } from '@/app/providers/ThemeProvider'
import { useNavigationContext } from '@/app/providers/use-navigation-context'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { useAuthStore } from '@/stores/auth-store'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: {
    definition: {},
    version: {},
    brand_profile: { logo_url: 'https://example.com/logo.svg' },
    theme: {
      tokens: { 'color.primary': '#654321' },
      brand: {},
      source: 'theme_designer',
    },
    runtime_context: {},
    permissions: {},
    warnings: [],
    source: 'theme_framework',
  },
  personalizationRuntime: {
    preferences: [],
    favorites: [],
    recent_items: [],
    shortcuts: [],
    quick_actions: [],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: { collapsed: false },
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: {},
    warnings: [],
    source: 'personalization_framework',
    runtime_context: {
      organization_public_id: 'org-1',
      workspace_public_id: 'ws-1',
      membership_public_id: 'mem-1',
      status: 'ok',
      missing_tables: [],
    },
  },
  navigationMenus: [
    {
      menu_key: 'main',
      label: 'Main',
      groups: [
        {
          group_key: 'platform',
          label: 'Platform',
          items: [{ item_key: 'home', label: 'Home' }],
        },
      ],
      metadata: {},
    },
  ],
  permissions: ['audit.read'],
  roles: [],
  user: {
    public_id: 'user-1',
    display_name: 'User',
    email: 'user@example.com',
    status: 'active',
  },
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'heos_runtime',
}

function NavigationProbe() {
  const value = useNavigationContext()
  return <div>{value.menus[0]?.groups[0]?.label ?? 'empty'}</div>
}

describe('NavigationProvider', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    useAuthStore.getState().setHydratedRuntime(runtime)
  })

  it('exposes runtime navigation menus', () => {
    render(
      <HydratedRuntimeProvider>
        <NavigationProvider>
          <NavigationProbe />
        </NavigationProvider>
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByText('Platform')).toBeInTheDocument()
  })
})

describe('ThemeProvider', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    useAuthStore.getState().setHydratedRuntime(runtime)
  })

  it('applies runtime theme tokens to css variables', async () => {
    render(
      <HydratedRuntimeProvider>
        <ThemeProvider>
          <div>themed</div>
        </ThemeProvider>
      </HydratedRuntimeProvider>,
    )

    await waitFor(() => {
      expect(
        document.documentElement.style.getPropertyValue('--heos-theme-color-primary'),
      ).toBe('#654321')
    })
  })
})
