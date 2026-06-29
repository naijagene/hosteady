import type { SearchResult } from '@/api/types/search'
import { SearchResultGroup } from './SearchResultGroup'

interface CommandResultsProps {
  results: SearchResult[]
  activeIndex?: number
  activeIndexOffset?: number
  onSelect?: (result: SearchResult) => void
}

export function CommandResults({
  results,
  activeIndex = -1,
  activeIndexOffset = 0,
  onSelect,
}: CommandResultsProps) {
  const commands = results.filter((result) => result.type === 'command' || result.source === 'command')
  return (
    <SearchResultGroup
      label="Commands"
      results={commands}
      activeIndex={activeIndex}
      activeIndexOffset={activeIndexOffset}
      onSelect={onSelect}
    />
  )
}
