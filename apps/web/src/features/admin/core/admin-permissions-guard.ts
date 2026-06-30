export function canReadAdminSection(permissions: string[], required?: string | null): boolean {
  if (!required) return true
  if (permissions.length === 0) return true
  return permissions.includes(required)
}

export function canReadPlatformAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('platform.read') || permissions.includes('settings.read')
}

export function canReadOrganizationAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('organization.read') || permissions.includes('settings.read')
}

export function canReadWorkspaceAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('workspace.read') || permissions.includes('settings.read')
}

export function canReadRolesAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('roles.read') || permissions.includes('settings.read')
}

export function canReadPermissionsAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('permissions.read') || permissions.includes('settings.read')
}

export function canReadApplicationsAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('applications.read') || permissions.includes('settings.read')
}

export function canReadRuntimeAdmin(permissions: string[]): boolean {
  return permissions.length === 0 || permissions.includes('runtime.read') || permissions.includes('diagnostics.read') || permissions.includes('settings.read')
}

export const adminNavItems = [
  { key: 'overview', label: 'Overview', route: '/admin', permission: 'platform.read' },
  { key: 'profile', label: 'Profile', route: '/admin/profile', permission: null },
  { key: 'organization', label: 'Organization', route: '/admin/organization', permission: 'organization.read' },
  { key: 'workspaces', label: 'Workspaces', route: '/admin/workspaces', permission: 'workspace.read' },
  { key: 'applications', label: 'Applications', route: '/admin/applications', permission: 'applications.read' },
  { key: 'roles', label: 'Roles', route: '/admin/roles', permission: 'roles.read' },
  { key: 'permissions', label: 'Permissions', route: '/admin/permissions', permission: 'permissions.read' },
  { key: 'platform', label: 'Platform', route: '/admin/platform', permission: 'platform.read' },
  { key: 'runtime', label: 'Runtime', route: '/admin/runtime', permission: 'runtime.read' },
  { key: 'about', label: 'About', route: '/admin/about', permission: null },
] as const

export function filterAdminNavItems(permissions: string[]) {
  return adminNavItems.filter((item) => canReadAdminSection(permissions, item.permission))
}
