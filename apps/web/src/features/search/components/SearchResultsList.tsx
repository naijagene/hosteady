import type { SearchResult } from '@/api/types/search'
import { groupSearchResults } from '../core/universal-finder'
import { SearchResultGroup } from './SearchResultGroup'
import { SearchResultItem } from './SearchResultItem'

interface SearchResultsListProps {
  results: SearchResult[]
  activeIndex?: number
  grouped?: boolean
  onSelect?: (result: SearchResult) => void
}

function formatGroupLabel(key: string): string {
  if (key === 'command') {
    return 'Commands'
  }
  return key.charAt(0).toUpperCase() + key.slice(1)
}

export function SearchResultsList({
  results,
  activeIndex = -1,
  grouped = true,
  onSelect,
}: SearchResultsListProps) {
  if (results.length === 0) {
    return null
  }

  if (!grouped) {
    return (
      <div role="listbox" aria-label="Search results" data-testid="search-results-list">
        {results.map((result, index) => (
          <SearchResultItem
            key={result.id}
            result={result}
            active={activeIndex === index}
            onSelect={onSelect}
          />
        ))}
      </div>
    )
  }

  const groups = groupSearchResults(results)
  let offset = 0

  return (
    <div role="listbox" aria-label="Search results" data-testid="search-results-list">
      {Object.entries(groups).map(([key, groupResults]) => {
        const group = (
          <SearchResultGroup
            key={key}
            label={formatGroupLabel(key)}
            results={groupResults}
            activeIndex={activeIndex}
            activeIndexOffset={offset}
            onSelect={onSelect}
          />
        )
        offset += groupResults.length
        return group
      })}
    </div>
  )
}
