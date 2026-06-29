export function DocumentLoadingState() {
  return (
    <div
      className="text-sm text-muted-foreground"
      data-testid="document-loading-state"
      role="status"
      aria-busy="true"
    >
      Loading documents…
    </div>
  )
}
