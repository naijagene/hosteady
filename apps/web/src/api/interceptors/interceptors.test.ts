import { describe, expect, it, vi } from 'vitest'
import type { InternalAxiosRequestConfig } from 'axios'
import { attachRequestInterceptors } from '@/api/interceptors/request'
import { attachResponseErrorInterceptor } from '@/api/interceptors/response'
import { useAuthStore } from '@/stores/auth-store'

describe('request interceptor', () => {
  it('attaches bearer and tenant headers', () => {
    useAuthStore.setState({
      accessToken: 'token-123',
      expiresAt: new Date(Date.now() + 60_000).toISOString(),
      organization: {
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
      workspace: {
        public_id: 'ws-1',
        name: 'Default',
        slug: 'default',
        is_default: true,
        status: 'active',
      },
    })

    const interceptor = attachRequestInterceptors()
    const config = interceptor({
      headers: {},
    } as InternalAxiosRequestConfig)

    expect(config.headers.Authorization).toBe('Bearer token-123')
    expect(config.headers['X-HEOS-Organization-Id']).toBe('org-1')
    expect(config.headers['X-HEOS-Workspace-Id']).toBe('ws-1')
  })

  it('rejects expired sessions', () => {
    useAuthStore.setState({
      accessToken: 'token-123',
      expiresAt: new Date(Date.now() - 60_000).toISOString(),
    })

    const onUnauthorized = vi.fn()
    const interceptor = attachRequestInterceptors(onUnauthorized)

    expect(() =>
      interceptor({ headers: {} } as InternalAxiosRequestConfig),
    ).toThrow('Session expired')
    expect(onUnauthorized).toHaveBeenCalled()
  })
})

describe('response interceptor', () => {
  it('routes unauthorized api errors to handler', async () => {
    const onUnauthorized = vi.fn()
    const interceptor = attachResponseErrorInterceptor({ onUnauthorized })

    await expect(
      interceptor({
        message: 'Unauthorized',
        response: { status: 401, data: { message: 'Unauthorized' } },
        isAxiosError: true,
      } as never),
    ).rejects.toMatchObject({ kind: 'unauthorized' })
    expect(onUnauthorized).toHaveBeenCalled()
  })

  it('routes forbidden api errors to handler', async () => {
    const onForbidden = vi.fn()
    const interceptor = attachResponseErrorInterceptor({ onForbidden })

    await expect(
      interceptor({
        message: 'Forbidden',
        response: { status: 403, data: { message: 'Forbidden' } },
        isAxiosError: true,
      } as never),
    ).rejects.toMatchObject({ kind: 'forbidden' })
    expect(onForbidden).toHaveBeenCalled()
  })
})

describe('refresh placeholder', () => {
  it('does not attempt refresh yet', async () => {
    const { shouldAttemptRefresh, refreshAccessTokenPlaceholder } = await import(
      '@/api/interceptors/auth'
    )

    expect(shouldAttemptRefresh()).toBe(false)
    await expect(refreshAccessTokenPlaceholder()).resolves.toBeNull()
  })
})
