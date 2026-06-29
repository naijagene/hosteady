import type { SearchResult } from '@/api/types/search'
import { SearchResultGroup } from './SearchResultGroup'

interface FavoriteResultsProps {
  results: SearchResult[]
  activeIndex?: number
  activeIndexOffset?: number
  onSelect?: (result: SearchResult) => void
}

export function FavoriteResults({
  results,
  activeIndex = -1,
  activeIndexOffset = 0,
  onSelect,
}: FavoriteResultsProps) {
  const favorites = results.filter((result) => result.type === 'favorite')
  return (
    <SearchResultGroup
      label="Favorites"
      results={favorites}
      activeIndex={activeIndex}
      activeIndexOffset={activeIndexOffset}
      onSelect={onSelect}
    />
  )
}
