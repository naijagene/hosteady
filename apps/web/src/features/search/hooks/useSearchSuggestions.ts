import { useMemo, useState } from 'react'
import { fetchSearchSuggestions } from '@/api/endpoints/search'
import { useQuery } from '@tanstack/react-query'
import { readRecentSearches, writeRecentSearch } from '../core/recent-searches-storage'

export function useSearchSuggestions(query: string) {
  const queryResult = useQuery({
    queryKey: ['search-suggestions', query],
    queryFn: () => fetchSearchSuggestions(query),
    enabled: query.trim().length > 0,
  })

  return {
    suggestions: queryResult.data ?? [],
    isLoading: queryResult.isLoading,
    error: queryResult.error,
  }
}

export function useRecentSearches() {
  const [recent, setRecent] = useState<string[]>(() => readRecentSearches())

  const addRecent = (query: string) => {
    const next = writeRecentSearch(query)
    setRecent(next)
    return next
  }

  const recentItems = useMemo(
    () =>
      recent.map((query) => ({
        query,
      })),
    [recent],
  )

  return {
    recent,
    recentItems,
    addRecent,
  }
}
