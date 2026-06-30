import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as adminApi from '@/api/endpoints/admin'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
  },
}))

describe('admin API endpoints', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches workspace runtime health', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: { health: 'healthy', summary: 'OK' } })
    const health = await adminApi.fetchWorkspaceRuntimeHealth()
    expect(health?.status).toBe('healthy')
  })

  it('returns null health on failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    expect(await adminApi.fetchWorkspaceRuntimeHealth()).toBeNull()
  })

  it('returns empty applications safely', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    expect(await adminApi.safeFetchTenantApplications()).toEqual([])
  })

  it('pings endpoint connectivity', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: {} })
    expect(await adminApi.pingApiEndpoint('tenant/context')).toBe(true)
  })
})
