import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/errors'
import { useAuthStore } from '@/stores/auth-store'

const fetchCurrentUser = vi.fn()
const fetchOrganizations = vi.fn()
const fetchTenantContext = vi.fn()
const hydrateRuntimeBundle = vi.fn()
const logoutRequest = vi.fn()

vi.mock('@/api/endpoints/auth', () => ({
  fetchCurrentUser: (...args: unknown[]) => fetchCurrentUser(...args),
  fetchOrganizations: (...args: unknown[]) => fetchOrganizations(...args),
  login: vi.fn(),
  logout: (...args: unknown[]) => logoutRequest(...args),
}))

vi.mock('@/api/endpoints/tenant', () => ({
  fetchTenantContext: (...args: unknown[]) => fetchTenantContext(...args),
}))

vi.mock('@/features/runtime/services/hydrate-runtime', () => ({
  hydrateRuntimeBundle: (...args: unknown[]) => hydrateRuntimeBundle(...args),
}))

const organization = {
  public_id: 'org-1',
  name: 'Org',
  slug: 'org',
  status: 'active' as const,
  organization_code: 'ORG001',
  membership: {
    public_id: 'mem-1',
    status: 'active' as const,
    join_method: 'invite' as const,
    default_workspace_public_id: 'ws-1',
  },
}

const workspace = {
  public_id: 'ws-1',
  name: 'Default',
  slug: 'default',
  is_default: true,
  status: 'active' as const,
}

describe('session-service', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    vi.clearAllMocks()

    fetchCurrentUser.mockResolvedValue({
      public_id: 'user-1',
      display_name: 'User',
      email: 'user@example.com',
      status: 'active',
    })
    fetchOrganizations.mockResolvedValue([organization])
    fetchTenantContext.mockResolvedValue({
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
      organization,
      membership: organization.membership,
      workspace,
      permissions: ['audit.read'],
      runtime_summary: {
        active_application_count: 0,
        runtime_version: 1,
        settings_version: 1,
      },
    })
    hydrateRuntimeBundle.mockResolvedValue({
      tenantContext: null,
      workspaceRuntime: null,
      themeRuntime: null,
      personalizationRuntime: null,
      navigationMenus: [],
      permissions: ['audit.read'],
      roles: [],
      user: null,
      organization,
      workspace,
      membership: organization.membership,
      application: null,
      unreadNotificationCount: 0,
      warnings: [],
      source: 'heos_runtime',
    })
    logoutRequest.mockResolvedValue(undefined)
  })

  it('clears session on stale token 401 during restore', async () => {
    fetchCurrentUser.mockRejectedValueOnce(
      new ApiError('Unauthorized', { kind: 'unauthorized', status: 401 }),
    )

    useAuthStore.getState().setAuthSession({
      accessToken: 'stale-token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })

    await useAuthStore.getState().restore()

    expect(useAuthStore.getState().accessToken).toBeNull()
    expect(useAuthStore.getState().phase).toBe('idle')
  })

  it('preserves session and marks error on 403 during bootstrap', async () => {
    fetchTenantContext.mockRejectedValueOnce(
      new ApiError('Forbidden', { kind: 'forbidden', status: 403 }),
    )

    useAuthStore.getState().setAuthSession({
      accessToken: 'token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })

    await useAuthStore.getState().restore()

    expect(useAuthStore.getState().accessToken).toBe('token')
    expect(useAuthStore.getState().phase).toBe('error')
  })

  it('preserves session on recoverable bootstrap failures', async () => {
    hydrateRuntimeBundle.mockRejectedValueOnce(
      new ApiError('Server unavailable', { kind: 'server', status: 500 }),
    )

    useAuthStore.getState().setAuthSession({
      accessToken: 'token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })

    await useAuthStore.getState().restore()

    expect(useAuthStore.getState().accessToken).toBe('token')
    expect(useAuthStore.getState().phase).toBe('error')
    expect(useAuthStore.getState().errorMessage).toContain('Server unavailable')
  })

  it('clears local session when backend logout fails', async () => {
    logoutRequest.mockRejectedValueOnce(new Error('network down'))
    useAuthStore.getState().setAuthSession({
      accessToken: 'token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })

    const { performLogout } = await import('./session-service')
    await performLogout()

    expect(useAuthStore.getState().accessToken).toBeNull()
    expect(useAuthStore.getState().hydratedRuntime).toBeNull()
  })
})

describe('tenant bootstrap state transitions', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
  })

  it('tracks bootstrap phases', () => {
    useAuthStore.getState().setPhase('bootstrapping')
    expect(useAuthStore.getState().phase).toBe('bootstrapping')
    useAuthStore.getState().setPhase('hydrating')
    expect(useAuthStore.getState().phase).toBe('hydrating')
    useAuthStore.getState().setPhase('ready')
    expect(useAuthStore.getState().phase).toBe('ready')
  })
})
