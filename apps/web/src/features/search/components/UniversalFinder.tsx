import { useMemo } from 'react'
import type { SearchResult } from '@/api/types/search'
import { shouldSearch } from '../core/search-query'
import { SearchEmptyState } from './SearchEmptyState'
import { SearchErrorState } from './SearchErrorState'
import { SearchLoadingState } from './SearchLoadingState'
import { SearchResultsList } from './SearchResultsList'
import { RecentSearches } from './RecentSearches'

interface UniversalFinderProps {
  query: string
  results: SearchResult[]
  recentSearches?: string[]
  isLoading?: boolean
  error?: string | null
  activeIndex?: number
  showRecent?: boolean
  onQuerySelect?: (query: string) => void
  onSelect?: (result: SearchResult) => void
}

export function UniversalFinder({
  query,
  results,
  recentSearches = [],
  isLoading = false,
  error = null,
  activeIndex = -1,
  showRecent = true,
  onQuerySelect,
  onSelect,
}: UniversalFinderProps) {
  const resultCountLabel = useMemo(() => `${results.length} results`, [results.length])

  return (
    <div data-testid="universal-finder">
      <p className="sr-only" aria-live="polite">
        {resultCountLabel}
      </p>

      {showRecent && !shouldSearch(query) ? <RecentSearches items={recentSearches} onSelect={onQuerySelect} /> : null}

      {isLoading ? <SearchLoadingState /> : null}
      {error ? <SearchErrorState message={error} /> : null}

      {!isLoading && !error && results.length > 0 ? (
        <SearchResultsList results={results} activeIndex={activeIndex} onSelect={onSelect} />
      ) : null}

      {!isLoading && !error && shouldSearch(query) && results.length === 0 ? (
        <SearchEmptyState query={query} />
      ) : null}

      {!isLoading && !error && !shouldSearch(query) && results.length === 0 ? <SearchEmptyState /> : null}
    </div>
  )
}
