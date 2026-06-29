import { Search } from '@/components/icons'
import { CommandPalette } from './CommandPalette'
import { useCommandPalette } from '../hooks/useCommandPalette'
import { useRecentSearches } from '../hooks/useSearchSuggestions'

export function GlobalSearchDialog() {
  const palette = useCommandPalette()
  const { recent } = useRecentSearches()

  return (
    <>
      <button
        type="button"
        className="inline-flex items-center gap-2 rounded-md border border-primary-foreground/20 px-3 py-1.5 text-sm text-primary-foreground hover:bg-primary-foreground/10"
        onClick={() => palette.setOpen(true)}
        aria-label={`Open search (${palette.shortcutHint})`}
        data-testid="global-search-trigger"
      >
        <Search className="h-4 w-4" aria-hidden />
        <span className="hidden md:inline">Search</span>
        <kbd className="hidden rounded border border-primary-foreground/20 px-1.5 py-0.5 text-[10px] lg:inline">
          {palette.shortcutHint}
        </kbd>
      </button>
      <CommandPalette palette={palette} recentSearches={recent} />
    </>
  )
}
