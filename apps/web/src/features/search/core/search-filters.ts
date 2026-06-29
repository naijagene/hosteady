import type { SearchFilter, SearchQueryPayload } from '@/api/types/search'

export function applySearchFilters(payload: SearchQueryPayload, filters: SearchFilter[]): SearchQueryPayload {
  return {
    ...payload,
    filters: [...(payload.filters ?? []), ...filters],
  }
}

export function filterSearchResultsByTypes<T extends { type?: string }>(
  items: T[],
  types?: Array<string>,
): T[] {
  if (!types || types.length === 0) {
    return items
  }

  return items.filter((item) => types.includes(item.type ?? 'custom'))
}

export function createSearchTypeFilter(type: string): SearchFilter {
  return { key: 'type', value: type }
}
