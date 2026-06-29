export function ReportLoadingState() {
  return (
    <div
      className="text-sm text-muted-foreground"
      data-testid="report-loading-state"
      role="status"
      aria-busy="true"
    >
      Loading report…
    </div>
  )
}
