interface DocumentViewToggleProps {
  viewMode: 'list' | 'grid'
  onChange: (viewMode: 'list' | 'grid') => void
}

export function DocumentViewToggle({ viewMode, onChange }: DocumentViewToggleProps) {
  return (
    <div className="inline-flex rounded-md border border-border" data-testid="document-view-toggle" role="group" aria-label="Document view mode">
      <button
        type="button"
        className={`px-3 py-1 text-xs ${viewMode === 'list' ? 'bg-muted text-foreground' : 'text-muted-foreground'}`}
        aria-pressed={viewMode === 'list'}
        aria-label="List view"
        onClick={() => onChange('list')}
      >
        List
      </button>
      <button
        type="button"
        className={`px-3 py-1 text-xs ${viewMode === 'grid' ? 'bg-muted text-foreground' : 'text-muted-foreground'}`}
        aria-pressed={viewMode === 'grid'}
        aria-label="Grid view"
        onClick={() => onChange('grid')}
      >
        Grid
      </button>
    </div>
  )
}
