import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  normalizeSearchResult,
  normalizeSearchQueryResult,
  normalizeSearchSuggestion,
  normalizeSearchRecentItem,
  normalizeSearchBindingContext,
  buildSearchQueryRequest,
} from '@/api/types/search'
import * as searchApi from '@/api/endpoints/search'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('search API types', () => {
  it('normalizes snake_case search results', () => {
    const result = normalizeSearchResult({
      public_id: 'doc-1',
      display_name: 'Invoice',
      entity_type: 'document',
      metadata: { route: '/documents/doc-1' },
    })
    expect(result.id).toBe('doc-1')
    expect(result.title).toBe('Invoice')
    expect(result.type).toBe('document')
    expect(result.source).toBe('backend')
  })

  it('normalizes camelCase search results', () => {
    const result = normalizeSearchResult({
      publicId: 'r-1',
      displayName: 'Summary',
      entityType: 'report',
    })
    expect(result.title).toBe('Summary')
    expect(result.type).toBe('report')
  })

  it('normalizes search query result payloads', () => {
    const payload = normalizeSearchQueryResult(
      {
        q: 'invoice',
        items: [{ public_id: '1', display_name: 'One', entity_type: 'document' }],
        total: 1,
      },
      'invoice',
    )
    expect(payload.query).toBe('invoice')
    expect(payload.items).toHaveLength(1)
    expect(payload.source).toBe('backend')
  })

  it('normalizes suggestions', () => {
    const suggestion = normalizeSearchSuggestion({ label: 'Docs', query: 'documents' })
    expect(suggestion.label).toBe('Docs')
    expect(suggestion.query).toBe('documents')
  })

  it('normalizes recent items', () => {
    const recent = normalizeSearchRecentItem({ query: 'home', occurred_at: '2024-01-01' })
    expect(recent.query).toBe('home')
    expect(recent.occurred_at).toBe('2024-01-01')
  })

  it('normalizes binding context', () => {
    const binding = normalizeSearchBindingContext({ mode: 'compact', showRecent: false })
    expect(binding.mode).toBe('compact')
    expect(binding.show_recent).toBe(false)
  })

  it('builds search query request with q alias', () => {
    const request = buildSearchQueryRequest({
      query: 'test',
      limit: 10,
      metadata: { source: 'web', context: 'command_palette' },
    })
    expect(request.q).toBe('test')
    expect(request.query).toBe('test')
    expect(request.limit).toBe(10)
  })
})

describe('search API endpoints', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('searches tenant via GET', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: { items: [{ public_id: '1', display_name: 'A', entity_type: 'page' }], total: 1 } },
    })
    const result = await searchApi.searchTenant({ query: 'a' })
    expect(result.items).toHaveLength(1)
    expect(apiClient.get).toHaveBeenCalledWith('tenant/search', expect.any(Object))
  })

  it('returns empty suggestions on failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    const suggestions = await searchApi.fetchSearchSuggestions('x')
    expect(suggestions).toEqual([])
  })

  it('returns empty recent on failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    const recent = await searchApi.fetchSearchRecent()
    expect(recent).toEqual([])
  })

  it('returns empty indexes on failure', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('fail'))
    const indexes = await searchApi.fetchSearchIndexes()
    expect(indexes).toEqual([])
  })
})
