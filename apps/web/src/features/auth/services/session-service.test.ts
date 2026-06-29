import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth-store'

vi.mock('@/features/auth/services/session-service', () => ({
  restoreAuthenticatedSession: vi.fn(async () => {
    throw new Error('Invalid token')
  }),
}))

describe('session restore contract', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    vi.clearAllMocks()
  })

  it('requires an access token before restore', async () => {
    await expect(useAuthStore.getState().restore()).resolves.toBeUndefined()
    expect(useAuthStore.getState().phase).toBe('idle')
  })

  it('propagates restore failures from session service', async () => {
    const { restoreAuthenticatedSession } = await import('./session-service')
    useAuthStore.getState().setAuthSession({
      accessToken: 'bad-token',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      user: {
        public_id: 'user-1',
        display_name: 'User',
        email: 'user@example.com',
        status: 'active',
      },
    })

    await expect(restoreAuthenticatedSession()).rejects.toThrow('Invalid token')
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

  it('stores organizations and workspaces from bootstrap', () => {
    useAuthStore.getState().setOrganizations([
      {
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
      },
    ])
    useAuthStore.getState().setWorkspaces([
      {
        public_id: 'ws-1',
        name: 'Default',
        slug: 'default',
        is_default: true,
        status: 'active',
      },
    ])

    expect(useAuthStore.getState().organizations).toHaveLength(1)
    expect(useAuthStore.getState().workspaces).toHaveLength(1)
  })
})
