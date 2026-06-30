import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth-store'

describe('auth store', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
  })

  it('tracks authenticated state from access token', () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'abc',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'Test User',
        email: 'test@example.com',
        status: 'active',
      },
    })

    expect(useAuthStore.getState().isAuthenticated()).toBe(true)
  })

  it('detects expired sessions', () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'abc',
      expiresAt: new Date(Date.now() - 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'Test User',
        email: 'test@example.com',
        status: 'active',
      },
    })

    expect(useAuthStore.getState().isSessionExpired()).toBe(true)
    expect(useAuthStore.getState().isAuthenticated()).toBe(false)
  })

  it('stores tenant scope ids', () => {
    useAuthStore.getState().setOrganization({
      public_id: 'org-1',
      name: 'Org',
      slug: 'org',
      status: 'active',
      organization_code: 'ORG001',
      membership: {
        public_id: 'mem-1',
        status: 'active',
        join_method: 'invite',
        default_workspace_public_id: 'ws-1',
      },
    })
    useAuthStore.getState().setWorkspace({
      public_id: 'ws-1',
      name: 'Workspace',
      slug: 'workspace',
      is_default: true,
      status: 'active',
    })

    expect(useAuthStore.getState().hasTenantScope()).toBe(true)
  })

  it('checks permissions from backend list', () => {
    useAuthStore.getState().setPermissions(['personalization.read', 'audit.read'])
    expect(useAuthStore.getState().hasPermission('audit.read')).toBe(true)
    expect(useAuthStore.getState().hasPermission('forms.read')).toBe(false)
  })

  it('clears all auth state on logout clear', () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'abc',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'Test User',
        email: 'test@example.com',
        status: 'active',
      },
    })
    useAuthStore.getState().clearAuth()

    expect(useAuthStore.getState().accessToken).toBeNull()
    expect(useAuthStore.getState().hydratedRuntime).toBeNull()
    expect(useAuthStore.getState().phase).toBe('idle')
  })

  it('clears expired sessions before restore', async () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'abc',
      expiresAt: new Date(Date.now() - 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'Test User',
        email: 'test@example.com',
        status: 'active',
      },
    })

    await useAuthStore.getState().restore()

    expect(useAuthStore.getState().accessToken).toBeNull()
    expect(useAuthStore.getState().phase).toBe('idle')
  })

  it('retryBootstrap resets error state before restoring', async () => {
    useAuthStore.getState().setAuthSession({
      accessToken: 'abc',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'Test User',
        email: 'test@example.com',
        status: 'active',
      },
    })
    useAuthStore.getState().setPhase('error')
    useAuthStore.getState().setErrorMessage('Failed')

    const restoreSpy = vi.spyOn(useAuthStore.getState(), 'restore').mockResolvedValue()

    await useAuthStore.getState().retryBootstrap()

    expect(useAuthStore.getState().phase).toBe('idle')
    expect(useAuthStore.getState().errorMessage).toBeNull()
    expect(restoreSpy).toHaveBeenCalled()
  })

  it('persists only approved storage keys', () => {
    const partialize = useAuthStore.persist.getOptions().partialize
    expect(partialize).toBeTypeOf('function')

    const persisted = partialize?.({
      accessToken: 'token',
      refreshToken: null,
      expiresAt: '2026-01-01',
      rememberMe: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
      permissions: ['audit.read'],
    } as never)

    expect(persisted).toEqual({
      accessToken: 'token',
      refreshToken: null,
      expiresAt: '2026-01-01',
      rememberMe: true,
      organizationPublicId: 'org-1',
      workspacePublicId: 'ws-1',
    })
  })
})
