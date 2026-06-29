interface SearchErrorStateProps {
  message?: string
}

export function SearchErrorState({ message = 'Search is temporarily unavailable.' }: SearchErrorStateProps) {
  return (
    <div className="px-3 py-6 text-sm text-destructive" role="alert" data-testid="search-error-state">
      {message}
    </div>
  )
}
