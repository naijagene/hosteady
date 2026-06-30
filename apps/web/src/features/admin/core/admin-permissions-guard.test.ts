import { describe, expect, it } from 'vitest'
import {
  canReadAdminSection,
  canReadApplicationsAdmin,
  canReadOrganizationAdmin,
  canReadPermissionsAdmin,
  canReadPlatformAdmin,
  canReadRolesAdmin,
  canReadRuntimeAdmin,
  canReadWorkspaceAdmin,
  filterAdminNavItems,
} from './admin-permissions-guard'

describe('admin permissions guard', () => {
  it('allows all sections when permissions array is empty', () => {
    expect(canReadPlatformAdmin([])).toBe(true)
    expect(canReadOrganizationAdmin([])).toBe(true)
    expect(canReadWorkspaceAdmin([])).toBe(true)
    expect(canReadRolesAdmin([])).toBe(true)
    expect(canReadPermissionsAdmin([])).toBe(true)
    expect(canReadApplicationsAdmin([])).toBe(true)
    expect(canReadRuntimeAdmin([])).toBe(true)
  })

  it('requires platform.read for platform admin', () => {
    expect(canReadPlatformAdmin(['platform.read'])).toBe(true)
    expect(canReadPlatformAdmin(['documents.read'])).toBe(false)
    expect(canReadPlatformAdmin(['settings.read'])).toBe(true)
  })

  it('requires organization.read for organization admin', () => {
    expect(canReadOrganizationAdmin(['organization.read'])).toBe(true)
    expect(canReadOrganizationAdmin(['platform.read'])).toBe(false)
  })

  it('requires workspace.read for workspace admin', () => {
    expect(canReadWorkspaceAdmin(['workspace.read'])).toBe(true)
    expect(canReadWorkspaceAdmin(['roles.read'])).toBe(false)
  })

  it('requires roles.read for roles admin', () => {
    expect(canReadRolesAdmin(['roles.read'])).toBe(true)
    expect(canReadRolesAdmin(['permissions.read'])).toBe(false)
  })

  it('requires permissions.read for permissions admin', () => {
    expect(canReadPermissionsAdmin(['permissions.read'])).toBe(true)
    expect(canReadPermissionsAdmin(['applications.read'])).toBe(false)
  })

  it('requires applications.read for applications admin', () => {
    expect(canReadApplicationsAdmin(['applications.read'])).toBe(true)
    expect(canReadApplicationsAdmin(['runtime.read'])).toBe(false)
  })

  it('requires runtime or diagnostics read for runtime admin', () => {
    expect(canReadRuntimeAdmin(['runtime.read'])).toBe(true)
    expect(canReadRuntimeAdmin(['diagnostics.read'])).toBe(true)
    expect(canReadRuntimeAdmin(['platform.read'])).toBe(false)
  })

  it('allows sections without required permission', () => {
    expect(canReadAdminSection(['documents.read'], null)).toBe(true)
  })

  it('filters navigation items by permission', () => {
    const items = filterAdminNavItems(['permissions.read'])
    expect(items.some((item) => item.route === '/admin/permissions')).toBe(true)
    expect(items.some((item) => item.route === '/admin/roles')).toBe(false)
    expect(items.some((item) => item.route === '/admin/profile')).toBe(true)
  })
})
