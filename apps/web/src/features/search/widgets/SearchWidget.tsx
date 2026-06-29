import { useState } from 'react'
import { SearchInput } from '../components/SearchInput'
import { UniversalFinder } from '../components/UniversalFinder'
import { useGlobalSearch } from '../hooks/useGlobalSearch'
import { shouldSearch } from '../core/search-query'

export function SearchWidget() {
  const [query, setQuery] = useState('')
  const search = useGlobalSearch(query)
  const results = shouldSearch(query) ? search.results : search.emptySuggestions.items

  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="search-widget">
      <h2 className="mb-3 text-sm font-semibold text-foreground">Quick search</h2>
      <SearchInput
        value={query}
        onChange={setQuery}
        placeholder="Search HEOS…"
        ariaControls="search-widget-results"
      />
      <div id="search-widget-results" className="mt-3">
        <UniversalFinder query={query} results={results.slice(0, 6)} isLoading={search.isLoading} showRecent={false} />
      </div>
    </section>
  )
}
