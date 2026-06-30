import { describe, expect, it } from 'vitest'
import {
  normalizeAdminApplicationInfo,
  normalizeAdminBindingContext,
  normalizeAdminOrganizationInfo,
  normalizeAdminPermissionInfo,
  normalizeAdminPlatformHealth,
  normalizeAdminPlatformInfo,
  normalizeAdminRoleInfo,
  normalizeAdminUserProfile,
  normalizeAdminFeatureFlags,
  normalizeAdminWorkspaceInfo,
} from '@/api/types/admin'

describe('admin API types', () => {
  it('normalizes platform info', () => {
    const info = normalizeAdminPlatformInfo({ heos_version: '1.0', runtime_status: 'Hydrated' }, { frontend_version: '0.0.0' })
    expect(info.heos_version).toBe('1.0')
    expect(info.frontend_version).toBe('0.0.0')
  })

  it('normalizes organization info snake_case', () => {
    const org = normalizeAdminOrganizationInfo({ public_id: 'org-1', name: 'Acme', metadata: { timezone: 'UTC' } })
    expect(org.public_id).toBe('org-1')
    expect(org.timezone).toBe('UTC')
  })

  it('normalizes workspace info camelCase', () => {
    const workspace = normalizeAdminWorkspaceInfo({ publicId: 'ws-1', name: 'Main', isDefault: true })
    expect(workspace.public_id).toBe('ws-1')
    expect(workspace.is_default).toBe(true)
  })

  it('normalizes user profile', () => {
    const profile = normalizeAdminUserProfile({ display_name: 'Alex', email: 'alex@example.com' }, { roles: ['admin'] })
    expect(profile.display_name).toBe('Alex')
    expect(profile.roles).toEqual(['admin'])
  })

  it('normalizes role info', () => {
    const role = normalizeAdminRoleInfo({ role_key: 'admin', description: 'Administrator', permission_count: 5 })
    expect(role.role_key).toBe('admin')
    expect(role.permission_count).toBe(5)
  })

  it('normalizes permission info', () => {
    const permission = normalizeAdminPermissionInfo('documents.read')
    expect(permission.category).toBe('documents')
  })

  it('normalizes application info', () => {
    const app = normalizeAdminApplicationInfo({ public_id: 'app-1', name: 'Platform', key: 'platform' })
    expect(app.name).toBe('Platform')
  })

  it('normalizes platform health', () => {
    expect(normalizeAdminPlatformHealth({ health: 'healthy' }).status).toBe('healthy')
    expect(normalizeAdminPlatformHealth({ status: 'degraded' }).status).toBe('warning')
    expect(normalizeAdminPlatformHealth({ health: 'error' }).status).toBe('unavailable')
  })

  it('normalizes binding context', () => {
    const binding = normalizeAdminBindingContext({ mode: 'permission_browser', compact: true })
    expect(binding.mode).toBe('permission_browser')
    expect(binding.compact).toBe(true)
  })

  it('normalizes feature flags', () => {
    const flags = normalizeAdminFeatureFlags({ demo_preview: true, beta: false })
    expect(flags).toHaveLength(2)
    expect(flags[0].enabled).toBe(true)
  })
})
