import { useState } from 'react'
import type { SearchResultType } from '@/api/types/search'
import { filterSearchResultsByTypes } from '../core/search-filters'
import { shouldSearch } from '../core/search-query'
import { resolveSearchAction } from '../core/search-actions'
import { executeCommand } from '../core/command-actions'
import { writeRecentSearch } from '../core/recent-searches-storage'
import { useGlobalSearch } from '../hooks/useGlobalSearch'
import { useRecentSearches } from '../hooks/useSearchSuggestions'
import { SearchFilters } from '../components/SearchFilters'
import { SearchInput } from '../components/SearchInput'
import { UniversalFinder } from '../components/UniversalFinder'
import { useNavigate } from '@tanstack/react-router'

export function SearchPage() {
  const [query, setQuery] = useState('')
  const [typeFilter, setTypeFilter] = useState<SearchResultType | 'all'>('all')
  const [activeIndex, setActiveIndex] = useState(0)
  const navigate = useNavigate()
  const { recent } = useRecentSearches()
  const search = useGlobalSearch(query)

  const baseResults = shouldSearch(query) ? search.results : search.emptySuggestions.items
  const results =
    typeFilter === 'all' ? baseResults : filterSearchResultsByTypes(baseResults, [typeFilter])

  const activateResult = async (result: (typeof results)[number]) => {
    if (query.trim()) {
      writeRecentSearch(query)
    }

    const action = resolveSearchAction(result)
    if (action.action_type === 'navigate' && action.route) {
      await navigate({ to: action.route })
      return
    }

    if (action.action_type === 'execute_command' && action.command_key) {
      await executeCommand(action.command_key)
    }
  }

  return (
    <div className="mx-auto flex w-full max-w-4xl flex-col gap-4" data-testid="search-page">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">Search</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Find pages, documents, workflows, notifications, and commands across HEOS.
        </p>
      </div>

      <SearchInput value={query} onChange={setQuery} ariaControls="search-page-results" />
      <SearchFilters value={typeFilter} onChange={setTypeFilter} />

      <div id="search-page-results">
        <UniversalFinder
          query={query}
          results={results}
          recentSearches={recent}
          isLoading={search.isLoading}
          error={search.error?.message ?? null}
          activeIndex={activeIndex}
          onQuerySelect={setQuery}
          onSelect={(result) => {
            setActiveIndex(results.findIndex((entry) => entry.id === result.id))
            void activateResult(result)
          }}
        />
      </div>
    </div>
  )
}
