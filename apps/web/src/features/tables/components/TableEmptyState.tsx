interface TableEmptyStateProps {
  message?: string
}

export function TableEmptyState({
  message = 'No records found.',
}: TableEmptyStateProps) {
  return (
    <div
      className="px-4 py-8 text-center text-sm text-muted-foreground"
      data-testid="table-empty-state"
    >
      {message}
    </div>
  )
}
