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
  fetchNavigationDesignerRuntime: vi.fn(async () => ({ menus: [], warnings: [], source: 'navigation_designer' })),
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
    expect(runtime.navigationMenus[0]?.groups[0]?.group_key).toBe('main')
  })

  it('normalizes flat workspace navigation contributions into a default group', async () => {
    const { fetchWorkspaceRuntime, fetchApplicationNavigation } = await import('@/api/endpoints/runtime')
    vi.mocked(fetchApplicationNavigation).mockResolvedValueOnce([])
    vi.mocked(fetchWorkspaceRuntime).mockResolvedValueOnce({
      organization: { public_id: 'org-1' },
      workspace: { public_id: 'ws-1', name: 'Default' },
      membership: { public_id: 'mem-1' },
      active_applications: [],
      active_application: null,
      runtime_version: 3,
      settings_version: 1,
      runtime_metadata: {},
      capabilities: {},
      navigation: [
        { item_key: 'demo-home', label: 'Demo Home' },
        { item_key: 'demo-settings', label: 'Demo Settings' },
      ],
      feature_flags: {},
      module_diagnostics: {},
      settings_metadata: {},
    } as never)

    const runtime = await hydrateRuntimeBundle()

    expect(runtime.navigationMenus[0]?.groups[0]?.group_key).toBe('default')
    expect(runtime.navigationMenus[0]?.groups[0]?.label).toBe('Main')
    expect(runtime.navigationMenus[0]?.groups[0]?.items).toHaveLength(2)
  })

  it('fills missing group_key on application navigation menus', async () => {
    const { fetchApplicationNavigation } = await import('@/api/endpoints/runtime')
    vi.mocked(fetchApplicationNavigation).mockResolvedValueOnce([
      {
        menu_key: 'alpha-preview',
        label: 'Alpha Primary Navigation',
        groups: [{ label: 'Alpha Primary Navigation', items: [{ item_key: 'alpha-home', label: 'Alpha Preview Home' }] }],
        metadata: {},
      },
    ] as never)

    const runtime = await hydrateRuntimeBundle()

    expect(runtime.navigationMenus[0]?.groups[0]?.group_key).toBe('default')
    expect(runtime.navigationMenus[0]?.groups[0]?.items[0]?.label).toBe('Alpha Preview Home')
  })

  it('merges warnings from theme and personalization', async () => {
    const runtime = await hydrateRuntimeBundle()
    expect(runtime.warnings).toEqual([])
  })

  it('propagates unauthorized errors from critical runtime endpoints', async () => {
    const { fetchTenantContext } = await import('@/api/endpoints/tenant')
    const { ApiError } = await import('@/api/errors')
    vi.mocked(fetchTenantContext).mockRejectedValueOnce(
      new ApiError('Unauthorized', { kind: 'unauthorized', status: 401 }),
    )

    await expect(hydrateRuntimeBundle()).rejects.toMatchObject({ kind: 'unauthorized' })
  })

  it('propagates forbidden errors from critical runtime endpoints', async () => {
    const { fetchWorkspaceRuntime } = await import('@/api/endpoints/runtime')
    const { ApiError } = await import('@/api/errors')
    vi.mocked(fetchWorkspaceRuntime).mockRejectedValueOnce(
      new ApiError('Forbidden', { kind: 'forbidden', status: 403 }),
    )

    await expect(hydrateRuntimeBundle()).rejects.toMatchObject({ kind: 'forbidden' })
  })

  it('allows optional runtime endpoints to fail with network errors', async () => {
    const { fetchThemeRuntime } = await import('@/api/endpoints/runtime')
    const { ApiError } = await import('@/api/errors')
    vi.mocked(fetchThemeRuntime).mockRejectedValueOnce(
      new ApiError('Network error', { kind: 'network', status: null }),
    )

    const runtime = await hydrateRuntimeBundle()
    expect(runtime.themeRuntime).toBeNull()
  })
})
