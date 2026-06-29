import { describe, expect, it } from 'vitest'
import { renderHook } from '@testing-library/react'
import { useDashboardPersonalization, useHomePersonalization } from '@/features/dashboards/hooks/useDashboardPersonalization'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { ReactNode } from 'react'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [{ label: 'Home' }],
    recent_items: [{ label: 'Users' }],
    shortcuts: [{ label: 'Settings' }],
    quick_actions: [{ label: 'Create' }],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {
      hidden_widgets: ['hidden-widget'],
      collapsed_widgets: ['collapsed-widget'],
      widget_order: ['b', 'a'],
      layout_density: 'compact',
    },
    table_overrides: {},
    notification_preferences_reference: {},
    warnings: [],
    source: 'personalization-runtime',
    runtime_context: {
      organization_public_id: null,
      workspace_public_id: null,
      membership_public_id: null,
      status: 'ready',
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
  unreadNotificationCount: 0,
  warnings: [],
  source: 'runtime',
}

function wrapper({ children }: { children: ReactNode }) {
  useAuthStore.getState().setHydratedRuntime(runtime)
  return <HydratedRuntimeProvider>{children}</HydratedRuntimeProvider>
}

describe('dashboard personalization hooks', () => {
  it('reads dashboard overrides from hydrated runtime', () => {
    const { result } = renderHook(() => useDashboardPersonalization(), { wrapper })
    expect(result.current.hiddenWidgetKeys.has('hidden-widget')).toBe(true)
    expect(result.current.collapsedWidgetKeys.has('collapsed-widget')).toBe(true)
    expect(result.current.widgetOrder).toEqual(['b', 'a'])
    expect(result.current.layoutDensity).toBe('compact')
  })

  it('returns defaults when personalization disabled', () => {
    const { result } = renderHook(() => useDashboardPersonalization({ enabled: false }), { wrapper })
    expect(result.current.hiddenWidgetKeys.size).toBe(0)
    expect(result.current.layoutDensity).toBe('comfortable')
  })

  it('reads home personalization collections', () => {
    const { result } = renderHook(() => useHomePersonalization(), { wrapper })
    expect(result.current.favorites).toHaveLength(1)
    expect(result.current.recentItems).toHaveLength(1)
    expect(result.current.shortcuts).toHaveLength(1)
    expect(result.current.quickActions).toHaveLength(1)
    expect(result.current.source).toBe('personalization-runtime')
  })
})
