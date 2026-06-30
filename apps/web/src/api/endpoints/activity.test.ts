import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as activityApi from '@/api/endpoints/activity'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('activity API endpoints', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches activity feed via tenant/activity', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { items: [{ public_id: '1', action: 'created', summary: 'Created' }], total: 1 },
    })
    const result = await activityApi.fetchActivityFeed({ page: 1 })
    expect(result.items).toHaveLength(1)
    expect(apiClient.get).toHaveBeenCalledWith('tenant/activity', expect.any(Object))
  })

  it('falls back to audit/events when tenant/activity fails', async () => {
    vi.mocked(apiClient.get)
      .mockRejectedValueOnce(new Error('missing'))
      .mockResolvedValueOnce({
        data: { items: [{ public_id: '2', action: 'updated', summary: 'Updated' }], total: 1 },
      })
    const result = await activityApi.fetchActivityFeed()
    expect(result.items[0].public_id).toBe('2')
  })

  it('returns safe empty feed on safeFetchActivityFeed failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('offline'))
    const result = await activityApi.safeFetchActivityFeed()
    expect(result.items).toEqual([])
    expect(result.source).toBe('local')
  })

  it('returns empty audit summary on failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    const summary = await activityApi.fetchAuditSummary()
    expect(summary.total_events).toBe(0)
  })

  it('returns local empty entity history when no endpoints match', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('missing'))
    const result = await activityApi.fetchEntityHistory('custom', 'x-1')
    expect(result.items).toEqual([])
    expect(result.source).toBe('local')
  })

  it('labels activity sources', () => {
    expect(activityApi.labelActivitySource('backend')).toBe('Platform activity')
    expect(activityApi.labelActivitySource('workflow')).toBe('Workflow history')
  })
})
