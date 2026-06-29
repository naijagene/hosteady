import type { SearchResult } from '@/api/types/search'
import { SearchResultItem } from './SearchResultItem'

interface SearchResultGroupProps {
  label: string
  results: SearchResult[]
  activeIndexOffset?: number
  activeIndex?: number
  onSelect?: (result: SearchResult) => void
}

export function SearchResultGroup({
  label,
  results,
  activeIndexOffset = 0,
  activeIndex = -1,
  onSelect,
}: SearchResultGroupProps) {
  if (results.length === 0) {
    return null
  }

  return (
    <section role="group" aria-label={label} data-testid={`search-group-${label.toLowerCase().replace(/\s+/g, '-')}`}>
      <h3 className="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</h3>
      <div className="space-y-1">
        {results.map((result, index) => (
          <SearchResultItem
            key={result.id}
            result={result}
            active={activeIndex === activeIndexOffset + index}
            onSelect={onSelect}
          />
        ))}
      </div>
    </section>
  )
}
