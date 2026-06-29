interface DocumentEmptyStateProps {
  message?: string
}

export function DocumentEmptyState({
  message = 'No documents match your current filters.',
}: DocumentEmptyStateProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground"
      data-testid="document-empty-state"
      role="status"
    >
      {message}
    </div>
  )
}
