interface SearchEmptyStateProps {
  query?: string
}

export function SearchEmptyState({ query }: SearchEmptyStateProps) {
  return (
    <div className="px-3 py-8 text-center text-sm text-muted-foreground" data-testid="search-empty-state">
      {query?.trim() ? (
        <>
          <p>No results for &ldquo;{query}&rdquo;</p>
          <p className="mt-1 text-xs">Try a different keyword or use a command.</p>
        </>
      ) : (
        <p>Start typing to search pages, documents, workflows, and commands.</p>
      )}
    </div>
  )
}
