import type { SearchResult } from '@/api/types/search'
import { SearchInput } from './SearchInput'
import { UniversalFinder } from './UniversalFinder'
import { shouldSearch } from '../core/search-query'

export interface CommandPaletteController {
  open: boolean
  setOpen: (open: boolean) => void
  query: string
  setQuery: (query: string) => void
  activeIndex: number
  flatResults: SearchResult[]
  isLoading: boolean
  error: { message?: string } | null
  source: string
  activateResult: (result: SearchResult) => Promise<void>
}

interface CommandPaletteProps {
  palette: CommandPaletteController
  recentSearches?: string[]
}

export function CommandPalette({ palette, recentSearches = [] }: CommandPaletteProps) {
  if (!palette.open) {
    return null
  }

  const activeDescendant =
    palette.flatResults[palette.activeIndex]
      ? `search-result-${palette.flatResults[palette.activeIndex].id}`
      : undefined

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 sm:p-6"
      data-testid="command-palette-overlay"
      onClick={() => palette.setOpen(false)}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Command palette"
        className="mt-16 w-full max-w-2xl rounded-xl border border-border bg-card shadow-xl"
        data-testid="command-palette"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="border-b border-border p-3">
          <SearchInput
            value={palette.query}
            onChange={palette.setQuery}
            autoFocus
            ariaControls="command-palette-results"
            ariaActiveDescendant={activeDescendant}
          />
          <p className="mt-2 px-1 text-xs text-muted-foreground">
            {shouldSearch(palette.query)
              ? `Source: ${palette.source}. Use arrows to navigate, Enter to open, Escape to close.`
              : 'Recent items, favorites, shortcuts, and commands.'}
          </p>
        </div>

        <div id="command-palette-results" className="max-h-[min(60vh,32rem)] overflow-y-auto py-2">
          <UniversalFinder
            query={palette.query}
            results={palette.flatResults}
            recentSearches={recentSearches}
            isLoading={palette.isLoading}
            error={palette.error?.message ?? null}
            activeIndex={palette.activeIndex}
            onQuerySelect={palette.setQuery}
            onSelect={(result) => void palette.activateResult(result)}
          />
        </div>
      </div>
    </div>
  )
}
