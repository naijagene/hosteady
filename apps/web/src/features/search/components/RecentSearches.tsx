interface RecentSearchesProps {
  items: string[]
  onSelect?: (query: string) => void
}

export function RecentSearches({ items, onSelect }: RecentSearchesProps) {
  if (items.length === 0) {
    return null
  }

  return (
    <section aria-label="Recent searches" data-testid="recent-searches">
      <h3 className="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        Recent searches
      </h3>
      <div className="flex flex-wrap gap-2 px-3 pb-2">
        {items.map((query) => (
          <button
            key={query}
            type="button"
            className="rounded-full border border-border px-2.5 py-1 text-xs text-muted-foreground hover:bg-muted"
            onClick={() => onSelect?.(query)}
          >
            {query}
          </button>
        ))}
      </div>
    </section>
  )
}
