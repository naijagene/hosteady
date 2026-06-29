import type { SearchResult } from '@/api/types/search'
import { SearchResultGroup } from './SearchResultGroup'

interface ShortcutResultsProps {
  results: SearchResult[]
  activeIndex?: number
  activeIndexOffset?: number
  onSelect?: (result: SearchResult) => void
}

export function ShortcutResults({
  results,
  activeIndex = -1,
  activeIndexOffset = 0,
  onSelect,
}: ShortcutResultsProps) {
  const shortcuts = results.filter((result) => result.type === 'shortcut')
  return (
    <SearchResultGroup
      label="Shortcuts"
      results={shortcuts}
      activeIndex={activeIndex}
      activeIndexOffset={activeIndexOffset}
      onSelect={onSelect}
    />
  )
}
