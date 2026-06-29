interface WorkflowEmptyStateProps {
  message?: string
}

export function WorkflowEmptyState({
  message = 'No workflow items match your current filters.',
}: WorkflowEmptyStateProps) {
  return (
    <div
      className="rounded-md border border-dashed border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground"
      data-testid="workflow-empty-state"
      role="status"
    >
      {message}
    </div>
  )
}
