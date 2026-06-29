import { describe, expect, it, vi } from 'vitest'
import { hydrateRuntimeBundle } from '@/features/runtime/services/hydrate-runtime'

vi.mock('@/api/endpoints/tenant', () => ({
  fetchTenantContext: vi.fn(async () => ({
    user: { public_id: 'user-1', display_name: 'User', email: 'u@example.com', status: 'active' },
    organization: {
      public_id: 'org-1',
      name: 'Org',
      slug: 'org',
      status: 'active',
      organization_code: 'ORG001',
    },
    membership: {
      public_id: 'mem-1',
      status: 'active',
      join_method: 'invite',
      default_workspace_public_id: 'ws-1',
    },
    workspace: {
      public_id: 'ws-1',
      name: 'Default',
      slug: 'default',
      is_default: true,
      status: 'active',
    },
    permissions: ['audit.read'],
    runtime_summary: {
      active_application_count: 1,
      runtime_version: 1,
      settings_version: 1,
    },
  })),
}))

vi.mock('@/api/endpoints/runtime', () => ({
  fetchWorkspaceRuntime: vi.fn(async () => ({
    organization: { public_id: 'org-1' },
    workspace: { public_id: 'ws-1', name: 'Default' },
    membership: { public_id: 'mem-1' },
    active_applications: [],
    active_application: null,
    runtime_version: 3,
    settings_version: 1,
    runtime_metadata: {},
    capabilities: {},
    navigation: {
      groups: [
        {
          group_key: 'main',
          label: 'Main',
          items: [{ item_key: 'home', label: 'Home' }],
        },
      ],
    },
    feature_flags: {},
    module_diagnostics: {},
    settings_metadata: {},
  })),
  fetchThemeRuntime: vi.fn(async () => ({
    theme: { 'color.primary': '#112233' },
    brand_profile: { logo_url: 'https://example.com/logo.svg' },
    warnings: [],
    source: 'theme_framework',
  })),
  fetchPersonalizationRuntime: vi.fn(async () => ({
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
    runtime_context: { status: 'ok', missing_tables: [] },
  })),
  fetchApplicationNavigation: vi.fn(async () => []),
}))

vi.mock('@/api/endpoints/notifications', () => ({
  fetchUnreadNotificationCount: vi.fn(async () => 2),
}))

describe('hydrateRuntimeBundle', () => {
  it('loads workspace, theme, personalization, and permissions together', async () => {
    const runtime = await hydrateRuntimeBundle()

    expect(runtime.workspaceRuntime?.runtime_version).toBe(3)
    expect(runtime.themeRuntime?.source).toBe('theme_framework')
    expect(runtime.personalizationRuntime?.source).toBe('personalization_framework')
    expect(runtime.permissions).toEqual(['audit.read'])
    expect(runtime.unreadNotificationCount).toBe(2)
  })

  it('normalizes workspace navigation groups when app menus are empty', async () => {
    const runtime = await hydrateRuntimeBundle()
    expect(runtime.navigationMenus[0]?.groups[0]?.label).toBe('Main')
  })

  it('merges warnings from theme and personalization', async () => {
    const runtime = await hydrateRuntimeBundle()
    expect(runtime.warnings).toEqual([])
  })
})
