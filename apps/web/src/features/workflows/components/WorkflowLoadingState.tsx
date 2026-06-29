export function WorkflowLoadingState() {
  return (
    <div
      className="text-sm text-muted-foreground"
      data-testid="workflow-loading-state"
      role="status"
      aria-busy="true"
    >
      Loading workflow data…
    </div>
  )
}
