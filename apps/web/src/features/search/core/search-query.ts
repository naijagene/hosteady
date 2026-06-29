import type { SearchQueryPayload } from '@/api/types/search'

export function createInitialSearchQuery(overrides?: Partial<SearchQueryPayload>): SearchQueryPayload {
  return {
    query: '',
    types: [],
    filters: [],
    limit: 20,
    metadata: { source: 'web', context: 'command_palette' },
    ...overrides,
  }
}

export function mergeSearchQueryPayload(
  current: SearchQueryPayload,
  patch: Partial<SearchQueryPayload>,
): SearchQueryPayload {
  return { ...current, ...patch }
}

export function normalizeSearchQuery(query: string): string {
  return query.trim().toLowerCase()
}

export function shouldSearch(query: string): boolean {
  return query.trim().length > 0
}
