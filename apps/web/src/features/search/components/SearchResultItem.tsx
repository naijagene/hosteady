import type { SearchResult } from '@/api/types/search'
import { formatSearchSource, getSearchResultDescription, getSearchResultLabel } from '../core/search-normalizer'
import { resolveSearchIcon } from '../core/search-icons'

interface SearchResultItemProps {
  result: SearchResult
  active?: boolean
  onSelect?: (result: SearchResult) => void
}

export function SearchResultItem({ result, active = false, onSelect }: SearchResultItemProps) {
  const icon = resolveSearchIcon(result.type)

  return (
    <button
      type="button"
      id={`search-result-${result.id}`}
      role="option"
      aria-selected={active}
      data-testid={`search-result-${result.id}`}
      data-active={active ? 'true' : 'false'}
      className={`flex w-full items-start gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors ${
        active ? 'bg-accent text-accent-foreground' : 'hover:bg-muted'
      }`}
      onClick={() => onSelect?.(result)}
    >
      <span className="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded bg-muted text-[10px] font-semibold uppercase text-muted-foreground">
        {icon.slice(0, 2)}
      </span>
      <span className="min-w-0 flex-1">
        <span className="block truncate font-medium">{getSearchResultLabel(result)}</span>
        <span className="block truncate text-xs text-muted-foreground">
          {getSearchResultDescription(result)} · {formatSearchSource(result.source)}
        </span>
      </span>
    </button>
  )
}
