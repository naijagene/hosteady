import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asArray } from '../types/metadata-common'
import {
  buildSearchQueryRequest,
  normalizeSearchQueryResult,
  normalizeSearchRecentItem,
  normalizeSearchSuggestion,
  type SearchQueryPayload,
  type SearchQueryResult,
  type SearchRecentItem,
  type SearchSuggestion,
} from '../types/search'

export async function searchTenant(payload: SearchQueryPayload = {}): Promise<SearchQueryResult> {
  try {
    const response = await apiClient.get<SearchQueryResult | { data: SearchQueryResult }>('tenant/search', {
      params: buildSearchQueryRequest(payload),
    })
    return normalizeSearchQueryResult(unwrapData(response.data), payload.query ?? '')
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function postTenantSearch(payload: SearchQueryPayload = {}): Promise<SearchQueryResult> {
  try {
    const response = await apiClient.post<SearchQueryResult | { data: SearchQueryResult }>(
      'tenant/search',
      buildSearchQueryRequest(payload),
    )
    return normalizeSearchQueryResult(unwrapData(response.data), payload.query ?? '')
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchSearchSuggestions(query = ''): Promise<SearchSuggestion[]> {
  try {
    const response = await apiClient.get<SearchSuggestion[] | { data: SearchSuggestion[] }>(
      'tenant/search/suggestions',
      { params: { q: query } },
    )
    return asArray(unwrapData(response.data)).map(normalizeSearchSuggestion)
  } catch {
    return []
  }
}

export async function fetchSearchRecent(): Promise<SearchRecentItem[]> {
  try {
    const response = await apiClient.get<SearchRecentItem[] | { data: SearchRecentItem[] }>('tenant/search/recent')
    return asArray(unwrapData(response.data)).map(normalizeSearchRecentItem)
  } catch {
    return []
  }
}

export async function saveSearchRecent(query: string): Promise<void> {
  try {
    await apiClient.post('tenant/search/recent', { query })
  } catch {
    try {
      await apiClient.post('tenant/search/saved', { name: query, query })
    } catch {
      // local-only fallback handled in hook
    }
  }
}

export async function fetchSearchIndexes(): Promise<unknown[]> {
  try {
    const response = await apiClient.get<{ data: unknown[] } | unknown[]>('tenant/search/indexes')
    return asArray(unwrapData(response.data))
  } catch {
    return []
  }
}
