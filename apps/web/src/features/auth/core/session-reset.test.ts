import { beforeEach, describe, expect, it, vi } from 'vitest'
import { QueryClient } from '@tanstack/react-query'
import { registerQueryClient } from '@/app/providers/query-client'
import {
  clearPersistedAuthStorage,
  HEOS_AUTH_STORAGE_KEY,
  resetSession,
} from '@/features/auth/core/session-reset'
import { useAuthStore } from '@/stores/auth-store'

describe('resetSession', () => {
  beforeEach(() => {
    useAuthStore.getState().clearAuth()
    registerQueryClient(new QueryClient())
    localStorage.clear()
  })

  it('clears auth store, runtime state, and persisted storage', async () => {
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
    useAuthStore.getState().setPermissions(['audit.read'])
    useAuthStore.getState().setHydratedRuntime({
      tenantContext: null,
      workspaceRuntime: null,
      themeRuntime: null,
      personalizationRuntime: null,
      navigationMenus: [],
      permissions: ['audit.read'],
      roles: [],
      user: null,
      organization: null,
      workspace: null,
      membership: null,
      application: null,
      unreadNotificationCount: 0,
      warnings: [],
      source: 'heos_runtime',
    })
    localStorage.setItem(HEOS_AUTH_STORAGE_KEY, '{"state":{"accessToken":"token"}}')

    const clearSpy = vi.spyOn(useAuthStore.persist, 'clearStorage')

    await resetSession()

    expect(useAuthStore.getState().accessToken).toBeNull()
    expect(useAuthStore.getState().hydratedRuntime).toBeNull()
    expect(useAuthStore.getState().permissions).toEqual([])
    expect(clearSpy).toHaveBeenCalled()
    expect(JSON.parse(localStorage.getItem(HEOS_AUTH_STORAGE_KEY) ?? '{}')).toMatchObject({
      state: { accessToken: null },
    })
  })

  it('clears react query cache when registered', async () => {
    const client = new QueryClient()
    const clearSpy = vi.spyOn(client, 'clear')
    registerQueryClient(client)

    await resetSession()

    expect(clearSpy).toHaveBeenCalled()
  })

  it('clearPersistedAuthStorage removes heos auth key', () => {
    localStorage.setItem(HEOS_AUTH_STORAGE_KEY, '{"state":{}}')

    clearPersistedAuthStorage()

    expect(localStorage.getItem(HEOS_AUTH_STORAGE_KEY)).toBeNull()
  })
})
