import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchSearchSuggestions } from '@/api/endpoints/search'
import { useUniversalFinder } from './useUniversalFinder'
import { shouldSearch } from '../core/search-query'

export function useGlobalSearch(query: string) {
  const finder = useUniversalFinder(query, {
    include_backend: true,
    include_runtime: true,
    include_personalization: true,
    include_commands: true,
    limit: 20,
  })

  const defaultFinder = useUniversalFinder('', {
    include_backend: false,
    include_runtime: true,
    include_personalization: true,
    include_commands: true,
    limit: 12,
  })

  const suggestions = useQuery({
    queryKey: ['search-suggestions', query],
    queryFn: () => fetchSearchSuggestions(query),
    enabled: shouldSearch(query),
  })

  const emptySuggestions = useMemo(
    () => ({
      query: '',
      items: defaultFinder.defaultItems,
      total: defaultFinder.defaultItems.length,
      source: 'runtime' as const,
    }),
    [defaultFinder.defaultItems],
  )

  return {
    results: finder.results,
    emptySuggestions,
    source: finder.source,
    error: finder.error,
    isLoading: finder.isLoading,
    suggestions,
    refresh: finder.refresh,
  }
}
