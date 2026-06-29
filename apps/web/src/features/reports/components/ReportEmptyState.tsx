interface ReportEmptyStateProps {
  message?: string
}

export function ReportEmptyState({
  message = 'No report sections are available yet.',
}: ReportEmptyStateProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/20 p-4 text-sm text-muted-foreground"
      data-testid="report-empty-state"
      role="status"
    >
      {message}
    </div>
  )
}
