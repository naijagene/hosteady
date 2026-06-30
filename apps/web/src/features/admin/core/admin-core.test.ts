import { describe, expect, it } from 'vitest'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import { buildPlatformOverview } from './admin-platform'
import { buildOrganizationSettings, getOrganizationDisplayFields } from './admin-organization'
import { buildWorkspaceSettings } from './admin-workspace'
import { buildUserProfile } from './admin-profile'
import { buildRoleBrowser } from './admin-roles'
import { buildPermissionBrowser, filterPermissions, groupPermissionsByCategory } from './admin-permissions'
import { buildApplicationRegistry } from './admin-applications'
import { deriveRuntimeHealth, getHealthLabel, mergePlatformHealth } from './admin-health'
import { buildRuntimeDiagnostics } from './admin-diagnostics'
import { buildApiDiagnostics } from './admin-api-diagnostics'
import { buildFeatureFlags } from './admin-feature-flags'
import { buildAboutHeos } from './admin-about'
import { filterAdminNavItems, canReadPlatformAdmin } from './admin-permissions-guard'
import { adminRoutes } from './admin-navigation'

const runtime = {
  organization: { public_id: 'org-1', name: 'Acme', slug: 'acme', status: 'active', organization_code: 'ACME', membership: { public_id: 'm-1', status: 'active', join_method: 'invite', default_workspace_public_id: 'ws-1' } },
  workspace: { public_id: 'ws-1', name: 'Main', slug: 'main', is_default: true, status: 'active' },
  user: { public_id: 'u-1', display_name: 'Alex', email: 'alex@example.com', status: 'active' },
  workspaceRuntime: {
    runtime_version: 3,
    active_applications: [{ public_id: 'app-1', name: 'Platform' }],
    feature_flags: { demo_preview: true },
    capabilities: { forms: 2, tables: 1 },
    settings_metadata: { timezone: 'UTC' },
  },
  themeRuntime: { source: 'themes' },
  personalizationRuntime: {
    favorites: [{}],
    recent_items: [{}],
    shortcuts: [],
    quick_actions: [],
    runtime_context: { missing_tables: [] },
  },
  navigationMenus: [{ menu_key: 'main', label: 'Main', groups: [{ group_key: 'core', label: 'Core', items: [{ item_key: 'home', label: 'Home' }] }], metadata: {} }],
  permissions: ['platform.read', 'documents.read'],
  roles: ['admin'],
  warnings: [],
  unreadNotificationCount: 2,
  source: 'heos_runtime',
} as unknown as HydratedRuntimeBundle

describe('admin platform core', () => {
  it('builds platform overview', () => {
    const overview = buildPlatformOverview(runtime, { applications: 1, navigation: 1 })
    expect(overview.heos_version).toContain('HEOS')
    expect(overview.feature_counts?.applications).toBe(1)
  })

  it('builds organization settings', () => {
    expect(buildOrganizationSettings(runtime).name).toBe('Acme')
    expect(getOrganizationDisplayFields(buildOrganizationSettings(runtime)).length).toBeGreaterThan(0)
  })

  it('builds workspace settings', () => {
    const settings = buildWorkspaceSettings(runtime, [runtime.workspace!])
    expect(settings.current.name).toBe('Main')
    expect(settings.workspaces).toHaveLength(1)
  })

  it('builds user profile', () => {
    const profile = buildUserProfile(runtime, '2025-01-01')
    expect(profile.email).toBe('alex@example.com')
    expect(profile.roles).toContain('admin')
  })
})

describe('admin roles and permissions', () => {
  it('builds role browser', () => {
    expect(buildRoleBrowser(['admin'], runtime.permissions)[0].role_key).toBe('admin')
  })

  it('infers roles from permissions when roles empty', () => {
    expect(buildRoleBrowser([], ['documents.read', 'reports.read']).length).toBeGreaterThan(0)
  })

  it('builds and filters permissions', () => {
    const permissions = buildPermissionBrowser(runtime.permissions)
    expect(filterPermissions(permissions, 'documents').length).toBe(1)
    expect(Object.keys(groupPermissionsByCategory(permissions)).length).toBeGreaterThan(0)
  })

  it('filters admin nav by permission', () => {
    expect(filterAdminNavItems(['platform.read']).some((item) => item.route === '/admin')).toBe(true)
    expect(canReadPlatformAdmin(['documents.read'])).toBe(false)
  })
})

describe('admin applications and health', () => {
  it('builds application registry', () => {
    const registry = buildApplicationRegistry(runtime)
    expect(registry.applications.length).toBeGreaterThan(0)
    expect(registry.totals.applications).toBeGreaterThan(0)
  })

  it('derives runtime health', () => {
    expect(deriveRuntimeHealth(runtime).status).toBe('healthy')
    expect(deriveRuntimeHealth(null).status).toBe('unavailable')
  })

  it('merges backend and runtime health', () => {
    const merged = mergePlatformHealth({ status: 'healthy', source: 'backend' }, deriveRuntimeHealth(runtime))
    expect(getHealthLabel(merged.status)).toBe('Healthy')
  })
})

describe('admin diagnostics and flags', () => {
  it('builds runtime diagnostics', () => {
    expect(buildRuntimeDiagnostics(runtime).some((item) => item.key === 'search')).toBe(true)
  })

  it('builds api diagnostics', () => {
    const diagnostics = buildApiDiagnostics({ authenticated: true })
    expect(diagnostics.base_url).toContain('/api/v1')
    expect(diagnostics.connected_endpoints.length).toBeGreaterThan(0)
  })

  it('builds feature flags', () => {
    expect(buildFeatureFlags(runtime)[0].key).toBe('demo_preview')
    expect(buildFeatureFlags(null)[0].enabled).toBe(false)
  })

  it('builds about heos', () => {
    expect(buildAboutHeos().platform).toBe('HEOS Platform')
  })

  it('defines admin routes', () => {
    expect(adminRoutes.root).toBe('/admin')
    expect(adminRoutes.permissions).toBe('/admin/permissions')
  })
})
